<?php

declare(strict_types=1);

require_once __DIR__ . "/../bootstrap.php";

use App\Auth\Middleware\CsrfMiddleware;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\AuthService;
use App\Auth\Service\RateLimiter;
use App\Evaluation\Repository\EvaluationRepository;
use App\Evaluation\Service\EvaluationService;
use App\Shared\AuditLogger;
use App\Shared\Session;

Session::start();

$userRepo = new UserRepository();
$rateLimiter = new RateLimiter();
$auditLogger = new AuditLogger();
$authService = new AuthService(
    $userRepo,
    $rateLimiter,
    (int)$_ENV["BCRYPT_COST"],
    (int)$_ENV["ACCOUNT_LOCKOUT_ATTEMPTS"],
    (int)$_ENV["ACCOUNT_LOCKOUT_DURATION"],
    $_ENV["ALLOWED_EMAIL_DOMAIN"],
    departments()
);

$user = $authService->requireAuth();

$evalRepo = new EvaluationRepository();
$evalService = new EvaluationService($evalRepo, $userRepo, $rateLimiter, $auditLogger);

/**
 * Render a template into a string (templates echo; layout expects $content).
 */
function render_template(string $template, array $data): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . "/../templates/" . $template;
    return (string)ob_get_clean();
}

/**
 * Verify CSRF token for POST requests; aborts with 403 on failure.
 */
function require_csrf(): void
{
    if (!CsrfMiddleware::verify($_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        $_SESSION["_flash"]["error"][] = "Invalid or expired session token. Please try again.";
        header("Location: /evaluations.php");
        exit;
    }
}

function is_post(): bool
{
    return $_SERVER["REQUEST_METHOD"] === "POST";
}

function method_not_allowed(): never
{
    http_response_code(405);
    exit;
}

$action = $_GET["action"] ?? "dashboard";

$pageTitle = "Evaluations - Faculty Evaluation System";
$showNavbar = true;

$viewData = [
    "user" => $user,
    "pageTitle" => $pageTitle,
    "showNavbar" => $showNavbar,
    "activePeriod" => $evalService->getActivePeriod(),
];

switch ($action) {
    case "dashboard":
        $viewData["stats"] = $evalService->statsForActivePeriod();
        $viewData["myEvaluations"] = $evalService->listMyEvaluations($user);
        $viewData["receivedEvaluations"] = $evalService->listReceivedEvaluations($user);
        $viewData["colleagues"] = $userRepo->listActiveUsers($user->id);
        $content = render_template("evaluation/dashboard.php", $viewData);
        break;

    case "fill":
        $evaluateeId = (int)($_GET["evaluatee_id"] ?? 0);
        if ($evaluateeId <= 0) {
            $_SESSION["_flash"]["error"][] = "Invalid evaluatee.";
            redirect("/evaluations.php");
        }

        try {
            $evaluation = $evalService->seedEvaluationForEvaluatee($evaluateeId, $user);
        } catch (Exception $e) {
            $_SESSION["_flash"]["error"][] = $e->getMessage();
            redirect("/evaluations.php");
        }

        $criteria = $evalService->listCriteria();
        $responses = $evalRepo->getResponsesByEvaluation($evaluation->id);

        $responseMap = [];
        foreach ($responses as $r) {
            $responseMap[$r->criterionId] = $r;
        }

        $viewData["evaluation"] = $evaluation;
        $viewData["criteria"] = $criteria;
        $viewData["responses"] = $responseMap;
        $content = render_template("evaluation/fill.php", $viewData);
        break;

    case "save_response":
        if (!is_post()) {
            method_not_allowed();
        }
        require_csrf();

        $evaluationId = (int)($_POST["evaluation_id"] ?? 0);
        $criterionId = (int)($_POST["criterion_id"] ?? 0);
        $score = (int)($_POST["score"] ?? -1);
        $comments = trim((string)($_POST["comments"] ?? ""));
        if (mb_strlen($comments) > 2000) {
            $comments = mb_substr($comments, 0, 2000);
        }

        header("Content-Type: application/json");
        try {
            $evalService->saveResponse($evaluationId, $criterionId, $score, $comments, $user);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        exit;

    case "submit":
        if (!is_post()) {
            method_not_allowed();
        }
        require_csrf();

        $evaluationId = (int)($_POST["evaluation_id"] ?? 0);
        $evaluateeId = (int)($_POST["evaluatee_id"] ?? 0);

        header("Content-Type: application/json");
        try {
            $evalService->submitEvaluation($evaluationId, $user);
            echo json_encode(["success" => true, "redirect" => "/evaluations.php?action=dashboard"]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage(),
                "redirect" => "/evaluations.php?action=fill&evaluatee_id=" . $evaluateeId,
            ]);
        }
        exit;

    case "admin":
        if (!$user->role->canManageUsers()) {
            http_response_code(403);
            exit;
        }

        $viewData["periods"] = $evalService->listPeriods();
        $viewData["criteria"] = $evalService->listCriteria();
        $viewData["stats"] = $evalService->statsForActivePeriod();
        $viewData["allEvaluations"] = $evalService->listAllForActivePeriod();
        $viewData["colleagues"] = $userRepo->listActiveUsers();
        $viewData["facultyCount"] = $userRepo->countActive();
        $content = render_template("evaluation/admin.php", $viewData);
        break;

    case "create_period":
        if (!is_post()) {
            method_not_allowed();
        }
        if (!$user->role->canManageUsers()) {
            http_response_code(403);
            exit;
        }
        require_csrf();

        try {
            $evalService->createPeriod([
                "name" => trim((string)($_POST["name"] ?? "")),
                "academic_year" => trim((string)($_POST["academic_year"] ?? "")),
                "semester" => trim((string)($_POST["semester"] ?? "")),
                "start_date" => (string)($_POST["start_date"] ?? ""),
                "end_date" => (string)($_POST["end_date"] ?? ""),
                "is_active" => isset($_POST["is_active"]) ? 1 : 0,
            ], $user);
            $_SESSION["_flash"]["success"][] = "Evaluation period created.";
        } catch (Exception $e) {
            $_SESSION["_flash"]["error"][] = $e->getMessage();
        }
        redirect("/evaluations.php?action=admin");

    case "activate_period":
        if (!is_post()) {
            method_not_allowed();
        }
        if (!$user->role->canManageUsers()) {
            http_response_code(403);
            exit;
        }
        require_csrf();

        $periodId = (int)($_POST["period_id"] ?? 0);
        try {
            $evalService->activatePeriod($periodId, $user);
            $_SESSION["_flash"]["success"][] = "Period activated.";
        } catch (Exception $e) {
            $_SESSION["_flash"]["error"][] = $e->getMessage();
        }
        redirect("/evaluations.php?action=admin");

    case "create_criterion":
        if (!is_post()) {
            method_not_allowed();
        }
        if (!$user->role->canManageUsers()) {
            http_response_code(403);
            exit;
        }
        require_csrf();

        try {
            $evalService->createCriterion([
                "category" => trim((string)($_POST["category"] ?? "")),
                "name" => trim((string)($_POST["name"] ?? "")),
                "description" => trim((string)($_POST["description"] ?? "")),
                "weight" => (float)($_POST["weight"] ?? 1.0),
                "min_score" => (int)($_POST["min_score"] ?? 1),
                "max_score" => (int)($_POST["max_score"] ?? 5),
                "is_active" => isset($_POST["is_active"]) ? 1 : 0,
                "display_order" => (int)($_POST["display_order"] ?? 0),
            ], $user);
            $_SESSION["_flash"]["success"][] = "Criterion created.";
        } catch (Exception $e) {
            $_SESSION["_flash"]["error"][] = $e->getMessage();
        }
        redirect("/evaluations.php?action=admin");

    case "change_status":
        if (!is_post()) {
            method_not_allowed();
        }
        if (!$user->role->canManageUsers()) {
            http_response_code(403);
            exit;
        }
        require_csrf();

        $evaluationId = (int)($_POST["evaluation_id"] ?? 0);
        $status = (string)($_POST["status"] ?? "");
        try {
            $evalService->changeEvaluationStatus($evaluationId, $status, $user);
            $_SESSION["_flash"]["success"][] = "Evaluation status updated.";
        } catch (Exception $e) {
            $_SESSION["_flash"]["error"][] = $e->getMessage();
        }
        redirect("/evaluations.php?action=admin");

    default:
        http_response_code(404);
        exit;
}

require __DIR__ . "/../templates/layout.php";

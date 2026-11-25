<?php

declare(strict_types=1);

require_once __DIR__ . "/../bootstrap.php";

use App\Auth\DTO\LoginDTO;
use App\Auth\DTO\RegisterDTO;
use App\Auth\Middleware\AuthMiddleware;
use App\Auth\Middleware\CsrfMiddleware;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\AuthService;
use App\Auth\Service\RateLimiter;
use App\Shared\Session;

Session::start();

$authService = new AuthService(
    new UserRepository(),
    new RateLimiter(
        (int)$_ENV["RATE_LIMIT_ATTEMPTS"],
        (int)$_ENV["RATE_LIMIT_WINDOW"]
    ),
    (int)$_ENV["BCRYPT_COST"],
    (int)$_ENV["ACCOUNT_LOCKOUT_ATTEMPTS"],
    (int)$_ENV["ACCOUNT_LOCKOUT_DURATION"],
    $_ENV["ALLOWED_EMAIL_DOMAIN"],
    departments()
);

$authMiddleware = new AuthMiddleware($authService);

if ($authMiddleware->handle() !== null) {
    redirect("/dashboard.php");
}

$mode = ($_GET["mode"] ?? "") === "register" ? "register" : "login";
$loginErrors = [];
$loginOld = [];
$registerErrors = [];
$registerOld = [];

// "Remember me" keeps only the email address in a long-lived cookie so the form
// can prefill it. The password itself is never stored by the application; the
// browser's own password manager supplies it from the saved credential.
$rememberedEmail = (string) ($_COOKIE["dorsu_remember_email"] ?? "");
if ($rememberedEmail !== "" && !isset($loginOld["email"])) {
    $loginOld["email"] = $rememberedEmail;
    $loginOld["remember_me"] = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = ($_POST["action"] ?? "login") === "register" ? "register" : "login";

    if (!CsrfMiddleware::verify($_POST["csrf_token"] ?? "")) {
        $csrfError = ["_csrf" => "Your session expired. Please refresh the page and try again."];

        if ($action === "register") {
            $mode = "register";
            $registerErrors = $csrfError;
        } else {
            $loginErrors = $csrfError;
        }
    } elseif ($action === "register") {
        $mode = "register";
        $dto = RegisterDTO::fromArray($_POST);
        $validationErrors = $dto->validate(departments(), $_ENV["ALLOWED_EMAIL_DOMAIN"]);

        if (($_POST["password"] ?? "") !== ($_POST["confirm_password"] ?? "")) {
            $validationErrors["confirm_password"] = "Passwords do not match";
        }

        if (!empty($validationErrors)) {
            $registerErrors = $validationErrors;
            $registerOld = $_POST;
        } else {
            try {
                $authService->register($dto);
                redirect("/dashboard.php");
            } catch (Exception $e) {
                $registerErrors["_auth"] = $e->getMessage();
                $registerOld = $_POST;
            }
        }
    } else {
        $dto = LoginDTO::fromArray($_POST);
        $validationErrors = $dto->validate($_ENV["ALLOWED_EMAIL_DOMAIN"]);

        if (!empty($validationErrors)) {
            $loginErrors = $validationErrors;
            $loginOld = $_POST;
        } else {
            try {
                $authService->login($dto);

                $cookieSecure = filter_var($_ENV["SESSION_SECURE_COOKIE"] ?? "false", FILTER_VALIDATE_BOOLEAN);
                if ($dto->rememberMe) {
                    setcookie("dorsu_remember_email", $dto->email, [
                        "expires" => time() + 60 * 60 * 24 * 30,
                        "path" => "/",
                        "secure" => $cookieSecure,
                        "httponly" => true,
                        "samesite" => "Lax",
                    ]);
                } else {
                    setcookie("dorsu_remember_email", "", [
                        "expires" => time() - 3600,
                        "path" => "/",
                        "secure" => $cookieSecure,
                        "httponly" => true,
                        "samesite" => "Lax",
                    ]);
                }

                redirect("/dashboard.php");
            } catch (Exception $e) {
                $loginErrors["_auth"] = $e->getMessage();
                $loginOld = $_POST;
            }
        }
    }
}

$pageTitle = $mode === "register"
    ? "Create Account - Faculty Evaluation System"
    : "Sign In - Faculty Evaluation System";
$showNavbar = false;

ob_start();
require __DIR__ . "/../templates/auth/auth-slider.php";
$content = ob_get_clean();
require __DIR__ . "/../templates/layout.php";

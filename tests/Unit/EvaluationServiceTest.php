<?php

declare(strict_types=1);

// Load environment variables before anything else
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->load();
$dotenv->required(["DB_CONNECTION", "DB_DATABASE", "SESSION_LIFETIME", "SESSION_REMEMBER_LIFETIME", "SESSION_SECURE_COOKIE", "SESSION_HTTP_ONLY", "SESSION_SAME_SITE", "BCRYPT_COST", "RATE_LIMIT_ATTEMPTS", "RATE_LIMIT_WINDOW", "ACCOUNT_LOCKOUT_ATTEMPTS", "ACCOUNT_LOCKOUT_DURATION", "ALLOWED_EMAIL_DOMAIN", "DEPARTMENTS"]);

use App\Auth\Repository\UserRepository;
use App\Auth\Service\RateLimiter;
use App\Evaluation\Repository\EvaluationRepository;
use App\Evaluation\Service\EvaluationService;
use App\Shared\AuditLogger;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../../bootstrap.php";

final class EvaluationServiceTest extends \PHPUnit\Framework\TestCase
{
    private UserRepository $userRepo;
    private RateLimiter $rateLimiter;
    private AuditLogger $auditLogger;
    private EvaluationRepository $evalRepo;
    private EvaluationService $evalService;

    protected function setUp(): void
    {
        $this->userRepo = new UserRepository();
        $this->rateLimiter = new RateLimiter(100, 60); // generous for tests
        $this->auditLogger = new AuditLogger();
        $this->evalRepo = new EvaluationRepository();
        $this->evalService = new EvaluationService(
            $this->evalRepo,
            $this->userRepo,
            $this->rateLimiter,
            $this->auditLogger
        );
    }

    public function testCreateAndActivatePeriod(): void
    {
        $user = $this->userRepo->findByEmail("admin@dorsu.edu.ph");
        if ($user === null) {
            $this->markTestSkipped("No admin user available");
        }

        $period = $this->evalService->createPeriod([
            "name" => "Test Period",
            "academic_year" => "2026-2027",
            "semester" => "1st",
            "start_date" => "2026-08-01",
            "end_date" => "2026-12-15",
            "is_active" => true,
        ], $user);

        $this->assertSame("Test Period", $period->name);
        $this->assertTrue($period->isActive);

        $active = $this->evalService->getActivePeriod();
        $this->assertNotNull($active);
        $this->assertSame($period->id, $active->id);
    }

    public function testCreateCriterion(): void
    {
        $user = $this->userRepo->findByEmail("admin@dorsu.edu.ph");
        if ($user === null) {
            $this->markTestSkipped("No admin user available");
        }

        $criterion = $this->evalService->createCriterion([
            "category" => "Test Category",
            "name" => "Test Criterion",
            "description" => "A test criterion",
            "weight" => 1.5,
            "min_score" => 1,
            "max_score" => 5,
            "is_active" => true,
            "display_order" => 1,
        ], $user);

        $this->assertSame("Test Criterion", $criterion->name);
        $this->assertSame(1.5, $criterion->weight);
        $this->assertSame(1, $criterion->minScore);
        $this->assertSame(5, $criterion->maxScore);
    }

    public function testListCriteria(): void
    {
        $criteria = $this->evalService->listCriteria();
        $this->assertIsArray($criteria);
        // Default criteria should exist from migration
        $this->assertGreaterThan(0, count($criteria));
    }

    public function testScoreValidation(): void
    {
        $user = $this->userRepo->findByEmail("admin@dorsu.edu.ph");
        if ($user === null) {
            $this->markTestSkipped("No admin user available");
        }

        // Create a period
        $period = $this->evalService->createPeriod([
            "name" => "Score Test Period",
            "academic_year" => "2026-2027",
            "semester" => "1st",
            "start_date" => "2026-08-01",
            "end_date" => "2026-12-15",
            "is_active" => true,
        ], $user);

        // Create an evaluatee
        $evaluatee = $this->userRepo->findByEmail("faculty1@dorsu.edu.ph");
        if ($evaluatee === null) {
            $this->markTestSkipped("No faculty user available");
        }

        // Seed evaluation
        $evaluation = $this->evalService->seedEvaluationForEvaluatee($evaluatee->id, $user);

        // Try to save invalid score (out of range)
        $this->expectException(\Exception::class);
        $this->evalService->saveResponse(
            $evaluation->id,
            1, // criterion id 1
            99, // out of range
            "test",
            $user
        );
    }
}

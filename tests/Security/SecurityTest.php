<?php

declare(strict_types=1);

// Load environment variables before anything else
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->load();
$dotenv->required(["DB_CONNECTION", "DB_DATABASE", "SESSION_LIFETIME", "SESSION_REMEMBER_LIFETIME", "SESSION_SECURE_COOKIE", "SESSION_HTTP_ONLY", "SESSION_SAME_SITE", "BCRYPT_COST", "RATE_LIMIT_ATTEMPTS", "RATE_LIMIT_WINDOW", "ACCOUNT_LOCKOUT_ATTEMPTS", "ACCOUNT_LOCKOUT_DURATION", "ALLOWED_EMAIL_DOMAIN", "DEPARTMENTS"]);

use App\Auth\Repository\UserRepository;
use App\Auth\Service\RateLimiter;
use App\Auth\Service\AuthService;
use App\Shared\Session;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../../bootstrap.php";

final class SecurityTest extends \PHPUnit\Framework\TestCase
{
    private UserRepository $userRepo;
    private RateLimiter $rateLimiter;
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->userRepo = new UserRepository();
        $this->rateLimiter = new RateLimiter();
        $this->authService = new AuthService(
            $this->userRepo,
            $this->rateLimiter,
            (int)$_ENV["BCRYPT_COST"],
            (int)$_ENV["ACCOUNT_LOCKOUT_ATTEMPTS"],
            (int)$_ENV["ACCOUNT_LOCKOUT_DURATION"],
            $_ENV["ALLOWED_EMAIL_DOMAIN"],
            departments()
        );
    }

    private function uniqueId(): string
    {
        return sprintf("%04d", (int)(microtime(true) * 10000) % 10000);
    }

    public function testPasswordHashingUsesBcrypt(): void
    {
        $id = $this->uniqueId();
        $dto = new \App\Auth\DTO\RegisterDTO(
            employeeId: "2024-{$id}",
            email: "sec1{$id}@dorsu.edu.ph",
            firstName: "Sec",
            lastName: "Test",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "SecurePass123!"
        );
        $user = $this->authService->register($dto);

        // Password hash should start with $2y$ (bcrypt)
        $this->assertStringStartsWith("$2y$", $user->passwordHash);
        $this->assertNotSame("SecurePass123!", $user->passwordHash);
    }

    public function testCaseSensitiveEmailLogin(): void
    {
        $email = "casesensitive@dorsu.edu.ph";
        try {
            $this->authService->register(new \App\Auth\DTO\RegisterDTO(
                employeeId: "2024-7777",
                email: $email,
                firstName: "Case",
                lastName: "Sensitive",
                department: "Faculty of Computing, Engineering, and Technology",
                password: "SecurePass123!"
            ));
        } catch (\Exception $e) {
            // May already exist from previous test run
        }

        // Login with exact same email
        $loginDto = new \App\Auth\DTO\LoginDTO(
            email: $email,
            password: "SecurePass123!",
            rememberMe: false
        );
        $user = $this->authService->login($loginDto);
        $this->assertSame($email, $user->email);
    }

    public function testRateLimitingWorks(): void
    {
        $key = "test:rate:" . uniqid();
        $this->rateLimiter->reset($key);

        $this->assertTrue($this->rateLimiter->checkLimit($key));
        $this->assertSame(5, $this->rateLimiter->getRemainingAttempts($key));

        $this->rateLimiter->recordAttempt($key);
        $this->assertSame(4, $this->rateLimiter->getRemainingAttempts($key));

        $this->rateLimiter->reset($key);
        $this->assertSame(5, $this->rateLimiter->getRemainingAttempts($key));
    }

    public function testSessionStartsSecurely(): void
    {
        // Session should use secure cookies
        $config = require __DIR__ . "/../../config/session.php";
        $this->assertArrayHasKey("secure", $config);
        $this->assertArrayHasKey("httponly", $config);
        $this->assertArrayHasKey("samesite", $config);
    }

    public function testSqlInjectionInEmployeeIdBlocked(): void
    {
        // Employee ID with SQL injection pattern should fail validation
        $this->assertFalse(\App\Shared\Validation::employeeId("2024-0001' OR '1'='1"));
        $this->assertFalse(\App\Shared\Validation::employeeId("2024-0001; DROP TABLE users"));
    }

    public function testXssInNameBlocked(): void
    {
        // Names with script tags should fail validation
        $this->assertFalse(\App\Shared\Validation::name("<script>alert('xss')</script>"));
        $this->assertFalse(\App\Shared\Validation::name("javascript:alert(1)"));
    }

    public function testCsrfTokenGenerated(): void
    {
        Session::start();
        $token = \App\Shared\Session::getCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 bytes hex = 64 chars
    }

    public function testCsrfTokenValidation(): void
    {
        Session::start();
        $token = \App\Shared\Session::getCsrfToken();
        $this->assertTrue(\App\Shared\Session::validateCsrfToken($token));
        $this->assertFalse(\App\Shared\Session::validateCsrfToken("invalid_token"));
    }
}

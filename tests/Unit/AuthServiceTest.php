<?php

declare(strict_types=1);

// Load environment variables before anything else
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->load();
$dotenv->required(["DB_CONNECTION", "DB_DATABASE", "SESSION_LIFETIME", "SESSION_REMEMBER_LIFETIME", "SESSION_SECURE_COOKIE", "SESSION_HTTP_ONLY", "SESSION_SAME_SITE", "BCRYPT_COST", "RATE_LIMIT_ATTEMPTS", "RATE_LIMIT_WINDOW", "ACCOUNT_LOCKOUT_ATTEMPTS", "ACCOUNT_LOCKOUT_DURATION", "ALLOWED_EMAIL_DOMAIN", "DEPARTMENTS"]);

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../../bootstrap.php";

use App\Auth\DTO\LoginDTO;
use App\Auth\DTO\RegisterDTO;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\AuthService;
use App\Auth\Service\RateLimiter;
use App\Shared\Session;

final class AuthServiceTest extends \PHPUnit\Framework\TestCase
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

    public function testRegisterAndLogin(): void
    {
        $id = $this->uniqueId();
        $employeeId = "2024-{$id}";
        $email = "testuser{$id}@dorsu.edu.ph";

        $dto = new RegisterDTO(
            employeeId: $employeeId,
            email: $email,
            firstName: "Test",
            lastName: "User",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "SecurePass123!"
        );

        $user = $this->authService->register($dto);
        $this->assertSame($employeeId, $user->employeeId);
        $this->assertSame($email, $user->email);
        $this->assertSame("Test User", $user->getFullName());

        // Login
        $loginDto = new LoginDTO(
            email: $email,
            password: "SecurePass123!",
            rememberMe: false
        );
        $loggedIn = $this->authService->login($loginDto);
        $this->assertSame($user->id, $loggedIn->id);
    }

    public function testDuplicateEmailRejected(): void
    {
        $id = $this->uniqueId();
        $email = "dup{$id}@dorsu.edu.ph";
        $dto = new RegisterDTO(
            employeeId: "2024-{$id}",
            email: $email,
            firstName: "Dup",
            lastName: "User",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "SecurePass123!"
        );
        $this->authService->register($dto);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("already exists");
        $this->authService->register($dto);
    }

    public function testWrongPasswordRejected(): void
    {
        $id = $this->uniqueId();
        $email = "wrongpass{$id}@dorsu.edu.ph";
        $dto = new RegisterDTO(
            employeeId: "2024-{$id}",
            email: $email,
            firstName: "Wrong",
            lastName: "Pass",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "SecurePass123!"
        );
        $this->authService->register($dto);

        $loginDto = new LoginDTO(
            email: $email,
            password: "WrongPass123!",
            rememberMe: false
        );
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid credentials");
        $this->authService->login($loginDto);
    }

    public function testNonDorsuEmailRejected(): void
    {
        $id = $this->uniqueId();
        $dto = new RegisterDTO(
            employeeId: "2024-{$id}",
            email: "user{$id}@gmail.com",
            firstName: "Non",
            lastName: "Dorsu",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "SecurePass123!"
        );
        $this->expectException(\Exception::class);
        $this->authService->register($dto);
    }

    public function testWeakPasswordRejected(): void
    {
        $id = $this->uniqueId();
        $dto = new RegisterDTO(
            employeeId: "2024-{$id}",
            email: "weak{$id}@dorsu.edu.ph",
            firstName: "Weak",
            lastName: "Pass",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "weak"
        );
        $this->expectException(\Exception::class);
        $this->authService->register($dto);
    }

    public function testAccountLockoutAfterFailedAttempts(): void
    {
        $id = $this->uniqueId();
        $email = "lockout{$id}@dorsu.edu.ph";
        $dto = new RegisterDTO(
            employeeId: "2024-{$id}",
            email: $email,
            firstName: "Lock",
            lastName: "Out",
            department: "Faculty of Computing, Engineering, and Technology",
            password: "SecurePass123!"
        );
        $this->authService->register($dto);

        $loginDto = new LoginDTO(
            email: $email,
            password: "WrongPass123!",
            rememberMe: false
        );

        // Fail 5 times to trigger lockout
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->authService->login($loginDto);
            } catch (\Exception $e) {
                // Expected
            }
        }

        // 6th attempt should trigger lockout or rate limiting
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/(locked|Too many login attempts)/');
        $this->authService->login($loginDto);
    }
}

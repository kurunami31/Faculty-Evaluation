<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\DTO\LoginDTO;
use App\Auth\DTO\RegisterDTO;
use App\Auth\Entity\User;
use App\Auth\Entity\UserRole;
use App\Auth\Entity\UserStatus;
use App\Auth\Repository\UserRepository;
use App\Shared\Session;
use DateTimeImmutable;
use Exception;

final class AuthService
{
    private UserRepository $userRepository;
    private RateLimiter $rateLimiter;
    private int $bcryptCost;
    private int $lockoutDuration;
    private string $allowedDomain;
    private array $allowedDepartments;

    public function __construct(
        UserRepository $userRepository,
        RateLimiter $rateLimiter,
        int $bcryptCost = 12,
        int $lockoutAttempts = 5,
        int $lockoutDuration = 900,
        string $allowedDomain = "dorsu.edu.ph",
        array $allowedDepartments = []
    ) {
        $this->userRepository = $userRepository;
        $this->rateLimiter = $rateLimiter;
        $this->bcryptCost = $bcryptCost;
        $this->lockoutDuration = $lockoutDuration;
        $this->allowedDomain = $allowedDomain;
        $this->allowedDepartments = $allowedDepartments;
    }

    public function register(RegisterDTO $dto): User
    {
        $errors = $dto->validate($this->allowedDepartments, $this->allowedDomain);

        if (!empty($errors)) {
            throw new Exception("Validation failed");
        }

        if ($this->userRepository->emailExists($dto->email)) {
            throw new Exception("An account with this email already exists");
        }

        if ($this->userRepository->employeeIdExists($dto->employeeId)) {
            throw new Exception("An account with this employee ID already exists");
        }

        $passwordHash = password_hash($dto->password, PASSWORD_BCRYPT, ["cost" => $this->bcryptCost]);

        $user = $this->userRepository->create([
            "employee_id" => $dto->employeeId,
            "email" => $dto->email,
            "password_hash" => $passwordHash,
            "first_name" => $dto->firstName,
            "last_name" => $dto->lastName,
            "department" => $dto->department,
            "role" => UserRole::Faculty->value,
            "status" => UserStatus::Active->value,
        ]);

        Session::setUserId($user->id);
        Session::regenerateId();

        return $user;
    }

    public function login(LoginDTO $dto): User
    {
        $errors = $dto->validate($this->allowedDomain);

        if (!empty($errors)) {
            throw new Exception("Invalid credentials");
        }

        $rateLimitKey = "login:" . $dto->email;

        if (!$this->rateLimiter->checkLimit($rateLimitKey)) {
            $resetTime = $this->rateLimiter->getResetTime($rateLimitKey);
            throw new Exception("Too many login attempts. Please try again in " . ceil($resetTime / 60) . " minutes.");
        }

        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null || !password_verify($dto->password, $user->passwordHash)) {
            $this->rateLimiter->recordAttempt($rateLimitKey);
            $this->userRepository->incrementFailedAttempts($dto->email);

            $attempts = $this->rateLimiter->getRemainingAttempts($rateLimitKey);
            if ($attempts === 0) {
                $lockoutUntil = new DateTimeImmutable("+" . $this->lockoutDuration . " seconds");
                $this->userRepository->lockAccount($dto->email, $lockoutUntil);
                throw new Exception("Account temporarily locked due to too many failed attempts.");
            }

            throw new Exception("Invalid credentials");
        }

        if (!$user->canLogin()) {
            throw new Exception("Account is not active or has been locked");
        }

        $this->rateLimiter->reset($rateLimitKey);
        $this->userRepository->resetFailedAttempts($dto->email);
        $this->userRepository->updateLastLogin($user->id);

        Session::setUserId($user->id);
        Session::regenerateId();

        return $user;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function getCurrentUser(): ?User
    {
        $userId = Session::getUserId();

        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    public function requireAuth(): User
    {
        $user = $this->getCurrentUser();

        if ($user === null) {
            throw new Exception("Authentication required");
        }

        return $user;
    }

    public function checkRateLimit(string $email): array
    {
        $key = "login:" . $email;
        return [
            "allowed" => $this->rateLimiter->checkLimit($key),
            "remaining" => $this->rateLimiter->getRemainingAttempts($key),
            "reset_in" => $this->rateLimiter->getResetTime($key),
        ];
    }
}

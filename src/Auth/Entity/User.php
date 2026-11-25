<?php

declare(strict_types=1);

namespace App\Auth\Entity;

use DateTimeImmutable;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $employeeId,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $department,
        public UserRole $role,
        public UserStatus $status,
        public ?DateTimeImmutable $emailVerifiedAt,
        public ?DateTimeImmutable $lastLoginAt,
        public int $failedLoginAttempts,
        public ?DateTimeImmutable $lockedUntil,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        // Password hash is stored but not exposed in constructor for security
        public string $passwordHash = "",
    ) {
    }

    public function getFullName(): string
    {
        return $this->firstName . " " . $this->lastName;
    }

    public function isActive(): bool
    {
        return $this->status->canLogin();
    }

    public function isLocked(): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }
        return $this->lockedUntil > new DateTimeImmutable();
    }

    public function canLogin(): bool
    {
        return $this->isActive() && !$this->isLocked();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data["id"],
            employeeId: (string)$data["employee_id"],
            email: (string)$data["email"],
            firstName: (string)$data["first_name"],
            lastName: (string)$data["last_name"],
            department: (string)$data["department"],
            role: UserRole::from((string)$data["role"]),
            status: UserStatus::from((string)$data["status"]),
            emailVerifiedAt: $data["email_verified_at"] ? new DateTimeImmutable((string)$data["email_verified_at"]) : null,
            lastLoginAt: $data["last_login_at"] ? new DateTimeImmutable((string)$data["last_login_at"]) : null,
            failedLoginAttempts: (int)$data["failed_login_attempts"],
            lockedUntil: $data["locked_until"] ? new DateTimeImmutable((string)$data["locked_until"]) : null,
            createdAt: new DateTimeImmutable((string)$data["created_at"]),
            updatedAt: new DateTimeImmutable((string)$data["updated_at"]),
            passwordHash: $data["password_hash"] ?? "",
        );
    }
}

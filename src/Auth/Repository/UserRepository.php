<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Entity\User;
use App\Auth\Entity\UserRole;
use App\Auth\Entity\UserStatus;
use App\Shared\Database;
use DateTimeImmutable;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(["email" => $email]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function findByEmployeeId(string $employeeId): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE employee_id = :employee_id LIMIT 1");
        $stmt->execute(["employee_id" => $employeeId]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function create(array $data): User
    {
        $sql = "INSERT INTO users (
            employee_id, email, password_hash, first_name, last_name,
            department, role, status, created_at, updated_at
        ) VALUES (
            :employee_id, :email, :password_hash, :first_name, :last_name,
            :department, :role, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            "employee_id" => $data["employee_id"],
            "email" => $data["email"],
            "password_hash" => $data["password_hash"],
            "first_name" => $data["first_name"],
            "last_name" => $data["last_name"],
            "department" => $data["department"],
            "role" => $data["role"] ?? UserRole::Faculty->value,
            "status" => $data["status"] ?? UserStatus::Active->value,
        ]);

        $id = (int)$this->db->lastInsertId();
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException("Failed to create user");
        }
        return $user;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(["id" => $id]);
    }

    public function incrementFailedAttempts(string $email): void
    {
        $stmt = $this->db->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1, updated_at = CURRENT_TIMESTAMP WHERE email = :email");
        $stmt->execute(["email" => $email]);
    }

    public function resetFailedAttempts(string $email): void
    {
        $stmt = $this->db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, updated_at = CURRENT_TIMESTAMP WHERE email = :email");
        $stmt->execute(["email" => $email]);
    }

    public function lockAccount(string $email, DateTimeImmutable $until): void
    {
        $stmt = $this->db->prepare("UPDATE users SET locked_until = :locked_until, updated_at = CURRENT_TIMESTAMP WHERE email = :email");
        $stmt->execute([
            "email" => $email,
            "locked_until" => $until->format("Y-m-d H:i:s"),
        ]);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(["email" => $email]);
        return $stmt->fetch() !== false;
    }

    public function employeeIdExists(string $employeeId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE employee_id = :employee_id LIMIT 1");
        $stmt->execute(["employee_id" => $employeeId]);
        return $stmt->fetch() !== false;
    }

    /**
     * All active users, optionally excluding one user id (e.g. the current user).
     * Used for the "evaluate a colleague" picker.
     *
     * @return User[]
     */
    public function listActiveUsers(?int $excludeId = null): array
    {
        $sql = "SELECT * FROM users WHERE status = :status";
        $params = ["status" => UserStatus::Active->value];

        if ($excludeId !== null) {
            $sql .= " AND id <> :exclude_id";
            $params["exclude_id"] = $excludeId;
        }

        $sql .= " ORDER BY last_name ASC, first_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(fn (array $row) => User::fromArray($row), $stmt->fetchAll());
    }

    public function countActive(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM users WHERE status = :status");
        $stmt->execute(["status" => UserStatus::Active->value]);
        $row = $stmt->fetch();
        return (int)$row["c"];
    }
}

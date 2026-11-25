<?php

declare(strict_types=1);

namespace App\Auth\Entity;

enum UserRole: string
{
    case Faculty = "faculty";
    case Admin = "admin";
    case Chair = "chair";

    public function label(): string
    {
        return match ($this) {
            self::Faculty => "Faculty",
            self::Admin => "Administrator",
            self::Chair => "Department Chair",
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isChair(): bool
    {
        return $this === self::Chair;
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::Admin, self::Chair], true);
    }
}

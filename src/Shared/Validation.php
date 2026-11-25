<?php

declare(strict_types=1);

namespace App\Shared;

final class Validation
{
    public static function email(string $email): bool
    {
        if (strlen($email) > 254) {
            return false;
        }
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function emailDomain(string $email, string $allowedDomain): bool
    {
        if (!self::email($email)) {
            return false;
        }
        $atPos = strrpos($email, "@");
        if ($atPos === false) {
            return false;
        }
        $domain = strtolower(substr($email, $atPos + 1));
        return $domain === strtolower($allowedDomain);
    }

    public static function employeeId(string $employeeId): bool
    {
        return (bool)preg_match("/^\d{4}-\d{4}$/", $employeeId);
    }

    public static function name(string $name): bool
    {
        $name = trim($name);
        return strlen($name) >= 1 && strlen($name) <= 100 && preg_match("/^[\p{L}\s\x27-]+$/u", $name);
    }

    public static function department(string $department, array $allowedDepartments): bool
    {
        return in_array($department, $allowedDepartments, true);
    }

    public static function password(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = "Password must be at least 12 characters long";
        }
        if (!preg_match("/[a-z]/", $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match("/[A-Z]/", $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match("/\d/", $password)) {
            $errors[] = "Password must contain at least one digit";
        }
        if (!preg_match("/[@\$!%*?&]/", $password)) {
            $errors[] = "Password must contain at least one special character (@\$!%*?&)";
        }

        return $errors;
    }

    public static function passwordStrength(string $password): int
    {
        $score = 0;
        if (strlen($password) >= 12) {
            $score++;
        }
        if (strlen($password) >= 16) {
            $score++;
        }
        if (preg_match("/[a-z]/", $password)) {
            $score++;
        }
        if (preg_match("/[A-Z]/", $password)) {
            $score++;
        }
        if (preg_match("/\d/", $password)) {
            $score++;
        }
        if (preg_match("/[@\$!%*?&]/", $password)) {
            $score++;
        }
        if (preg_match("/[^a-zA-Z0-9@\$!%*?&]/", $password)) {
            $score++;
        }
        return min($score, 5);
    }

    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8");
    }

    public static function sanitizeOutput(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, "UTF-8");
    }
}

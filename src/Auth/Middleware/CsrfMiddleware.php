<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\Shared\Session;

final class CsrfMiddleware
{
    public static function verify(string $token): bool
    {
        return Session::validateCsrfToken($token);
    }

    public static function getToken(): string
    {
        return Session::getCsrfToken();
    }

    public static function rotateToken(): void
    {
        Session::regenerateCsrfToken();
    }

    public static function getTokenField(): string
    {
        $token = self::getToken();
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($token, ENT_QUOTES, "UTF-8") . "\">";
    }
}

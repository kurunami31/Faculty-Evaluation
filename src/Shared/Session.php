<?php

declare(strict_types=1);

namespace App\Shared;

final class Session
{
    private static bool $started = false;
    private const FLASH_KEY = "_flash";
    private const CSRF_KEY = "_csrf_token";
    private const USER_KEY = "_user_id";
    private const REMEMBER_KEY = "_remember_me";

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        $config = require __DIR__ . "/../../config/session.php";

        session_name($config["name"]);

        $cookieParams = session_get_cookie_params();
        $path = $cookieParams["path"] ?? "/";
        $domain = $cookieParams["domain"] ?? "";

        session_set_cookie_params([
            "lifetime" => $config["lifetime"] * 60,
            "path" => $path,
            "domain" => $domain,
            "secure" => $config["secure"],
            "httponly" => $config["httponly"],
            "samesite" => $config["samesite"],
        ]);

        session_start();
        self::$started = true;

        if (empty($_SESSION[self::CSRF_KEY])) {
            self::regenerateCsrfToken();
        }
    }

    public static function regenerateId(bool $deleteOldSession = true): void
    {
        self::start();
        session_regenerate_id($deleteOldSession);
        self::regenerateCsrfToken();
    }

    public static function regenerateCsrfToken(): void
    {
        self::start();
        $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
    }

    public static function getCsrfToken(): string
    {
        self::start();
        return $_SESSION[self::CSRF_KEY] ?? "";
    }

    public static function validateCsrfToken(string $token): bool
    {
        self::start();
        $stored = $_SESSION[self::CSRF_KEY] ?? "";
        return hash_equals($stored, $token);
    }

    public static function setUserId(int $userId): void
    {
        self::start();
        $_SESSION[self::USER_KEY] = $userId;
    }

    public static function getUserId(): ?int
    {
        self::start();
        return $_SESSION[self::USER_KEY] ?? null;
    }

    public static function clearUserId(): void
    {
        self::start();
        unset($_SESSION[self::USER_KEY]);
    }

    public static function setRememberMe(bool $remember): void
    {
        self::start();
        $_SESSION[self::REMEMBER_KEY] = $remember;
    }

    public static function isRememberMe(): bool
    {
        self::start();
        return $_SESSION[self::REMEMBER_KEY] ?? false;
    }

    public static function setFlash(string $type, string $message): void
    {
        self::start();
        $_SESSION[self::FLASH_KEY][$type][] = $message;
    }

    public static function getFlashes(): array
    {
        self::start();
        $flashes = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);
        return $flashes;
    }

    public static function hasFlash(string $type): bool
    {
        self::start();
        return isset($_SESSION[self::FLASH_KEY][$type]) && count($_SESSION[self::FLASH_KEY][$type]) > 0;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $name = session_name();
            if ($name !== false && $name !== "") {
                $path = $params["path"] ?? "/";
                $domain = $params["domain"] ?? "";
                $secure = $params["secure"] ?? false;
                $httponly = $params["httponly"] ?? true;
                setcookie(
                    $name,
                    "",
                    time() - 42000,
                    $path,
                    $domain,
                    $secure,
                    $httponly
                );
            }
        }
        session_destroy();
        self::$started = false;
    }

    public static function extendLifetime(int $minutes): void
    {
        self::start();
        $config = require __DIR__ . "/../../config/session.php";
        $cookieParams = session_get_cookie_params();
        $path = $cookieParams["path"] ?? "/";
        $domain = $cookieParams["domain"] ?? "";

        session_set_cookie_params([
            "lifetime" => $minutes * 60,
            "path" => $path,
            "domain" => $domain,
            "secure" => $config["secure"],
            "httponly" => $config["httponly"],
            "samesite" => $config["samesite"],
        ]);
        session_start();
    }
}

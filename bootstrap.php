<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . "/");
$dotenv->load();
$dotenv->required(["DB_CONNECTION", "DB_DATABASE", "SESSION_LIFETIME", "SESSION_REMEMBER_LIFETIME", "SESSION_SECURE_COOKIE", "SESSION_HTTP_ONLY", "SESSION_SAME_SITE", "BCRYPT_COST", "RATE_LIMIT_ATTEMPTS", "RATE_LIMIT_WINDOW", "ACCOUNT_LOCKOUT_ATTEMPTS", "ACCOUNT_LOCKOUT_DURATION", "ALLOWED_EMAIL_DOMAIN", "DEPARTMENTS"]);

date_default_timezone_set("Asia/Manila");

ini_set("display_errors", $_ENV["APP_DEBUG"] === "true" ? "1" : "0");
ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/storage/logs/php-error.log");

if (!is_dir(__DIR__ . "/storage/logs")) {
    mkdir(__DIR__ . "/storage/logs", 0755, true);
}

function app(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

function config(string $path, mixed $default = null): mixed
{
    $keys = explode(".", $path);
    $config = require __DIR__ . "/config/" . array_shift($keys) . ".php";
    
    foreach ($keys as $key) {
        if (!isset($config[$key])) {
            return $default;
        }
        $config = $config[$key];
    }
    
    return $config;
}

function view(string $template, array $data = []): void
{
    extract($data);
    require __DIR__ . "/../templates/layout.php";
}

function redirect(string $url, int $statusCode = 302): never
{
    header("Location: " . $url, true, $statusCode);
    exit;
}

function old(string $key, string $default = ""): string
{
    return $_SESSION["_old"][$key] ?? $default;
}

function flash(string $type): array
{
    return $_SESSION["_flash"][$type] ?? [];
}

function hasFlash(string $type): bool
{
    return isset($_SESSION["_flash"][$type]) && count($_SESSION["_flash"][$type]) > 0;
}

function csrf_token(): string
{
    return \App\Shared\Session::getCsrfToken();
}

function csrf_field(): string
{
    return \App\Auth\Middleware\CsrfMiddleware::getTokenField();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function departments(): array
{
    $raw = $_ENV["DEPARTMENTS"] ?? "";
    // Pipe-separated (faculty names contain commas); fall back to comma
    $parts = str_contains($raw, "|") ? explode("|", $raw) : explode(",", $raw);
    return array_values(array_filter(array_map("trim", $parts), fn (string $d) => $d !== ""));
}

function faculty_slug(string $department): ?string
{
    static $map = [
        "Faculty of Computing, Engineering, and Technology" => "facet",
        "Faculty of Teacher Education" => "fted",
        "Faculty of Criminal Justice Education" => "fcje",
        "Faculty of Nursing and Allied Health Sciences" => "fnahs",
        "Faculty of Agriculture and Life Sciences" => "fals",
        "Faculty of Humanities, Social Sciences, and Communication" => "fhsc",
        "Faculty of Business and Management" => "fbm",
    ];
    return $map[$department] ?? null;
}

function faculty_logo(string $department): ?string
{
    $slug = faculty_slug($department);
    if ($slug === null) {
        return null;
    }
    $file = "faculties/" . $slug . ".png";
    return file_exists(__DIR__ . "/public/assets/img/" . $file)
        ? "/assets/img/" . $file
        : null;
}



<?php

declare(strict_types=1);

return [
    "lifetime" => (int)($_ENV["SESSION_LIFETIME"] ?? 120),
    "remember_lifetime" => (int)($_ENV["SESSION_REMEMBER_LIFETIME"] ?? 10080),
    "secure" => filter_var($_ENV["SESSION_SECURE_COOKIE"] ?? "false", FILTER_VALIDATE_BOOLEAN),
    "httponly" => filter_var($_ENV["SESSION_HTTP_ONLY"] ?? "true", FILTER_VALIDATE_BOOLEAN),
    "samesite" => $_ENV["SESSION_SAME_SITE"] ?? "strict",
    "name" => "faculty_eval_session",
];
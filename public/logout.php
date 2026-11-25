<?php

declare(strict_types=1);

require_once __DIR__ . "/../bootstrap.php";

use App\Auth\Service\AuthService;
use App\Auth\Service\RateLimiter;
use App\Auth\Repository\UserRepository;
use App\Shared\Session;

Session::start();

$authService = new AuthService(
    new UserRepository(),
    new RateLimiter(
        (int)$_ENV["RATE_LIMIT_ATTEMPTS"],
        (int)$_ENV["RATE_LIMIT_WINDOW"]
    ),
    (int)$_ENV["BCRYPT_COST"],
    (int)$_ENV["ACCOUNT_LOCKOUT_ATTEMPTS"],
    (int)$_ENV["ACCOUNT_LOCKOUT_DURATION"],
    $_ENV["ALLOWED_EMAIL_DOMAIN"],
    departments()
);

$authService->logout();

Session::setFlash("success", "You have been logged out successfully.");

redirect("/index.php");
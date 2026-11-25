<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\Auth\Service\AuthService;
use App\Shared\Session;

final class AuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(): ?object
    {
        Session::start();

        try {
            $user = $this->authService->requireAuth();
            return $user;
        } catch (\Exception) {
            return null;
        }
    }

    public function requireAuth(): object
    {
        $user = $this->handle();

        if ($user === null) {
            header("Location: /index.php");
            exit;
        }

        return $user;
    }

    public function redirectIfAuthenticated(string $redirectTo = "/dashboard.php"): void
    {
        Session::start();

        if (Session::getUserId() !== null) {
            header("Location: " . $redirectTo);
            exit;
        }
    }
}

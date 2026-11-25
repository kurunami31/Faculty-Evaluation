<?php

declare(strict_types=1);

require_once __DIR__ . "/../bootstrap.php";

use App\Auth\Middleware\AuthMiddleware;
use App\Auth\Service\AuthService;
use App\Auth\Service\RateLimiter;
use App\Auth\Repository\UserRepository;
use App\Evaluation\Repository\EvaluationRepository;
use App\Evaluation\Service\EvaluationService;
use App\Shared\AuditLogger;
use App\Shared\Session;

Session::start();

$userRepo = new UserRepository();
$rateLimiter = new RateLimiter();
$auditLogger = new AuditLogger();
$authService = new AuthService(
    $userRepo,
    $rateLimiter,
    (int)$_ENV["BCRYPT_COST"],
    (int)$_ENV["ACCOUNT_LOCKOUT_ATTEMPTS"],
    (int)$_ENV["ACCOUNT_LOCKOUT_DURATION"],
    $_ENV["ALLOWED_EMAIL_DOMAIN"],
    departments()
);

$authMiddleware = new AuthMiddleware($authService);
$user = $authMiddleware->requireAuth();

$evalRepo = new EvaluationRepository();
$evalService = new EvaluationService($evalRepo, $userRepo, $rateLimiter, $auditLogger);

$stats = $evalService->statsForActivePeriod();

$pageTitle = "Dashboard - Faculty Evaluation System";
$showNavbar = true;


ob_start();
?>
<div class="container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard</h1>
        <p class="dashboard-subtitle">Welcome back, <?= e($user->getFullName()) ?></p>
    </div>

    <?php if ($stats["period"]): ?>
        <div class="alert alert-info">
            Active evaluation period: <strong><?= e($stats["period"]->name) ?></strong>
            (<?= e($stats["period"]->getDateRange()) ?>)
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            No active evaluation period has been configured. Please contact an administrator.
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="stat-value"><?= e((string)($stats["total"] ?? 0)) ?></div>
            <div class="stat-label">Total Evaluations</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 12h-4l-3 3L7 18"/>
                </svg>
            </div>
            <div class="stat-value"><?= e((string)($stats["submitted"] ?? 0)) ?></div>
            <div class="stat-label">Submitted</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="stat-value"><?= e((string)($stats["draft"] ?? 0)) ?></div>
            <div class="stat-label">Draft</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <path d="M8 21h8M12 17v4"/>
                </svg>
            </div>
            <div class="stat-value stat-value--text"><?= e($user->department) ?></div>
            <div class="stat-label">Department</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Profile Information</h2>
        </div>
        <div class="card-body">
            <div class="user-profile">
                <?php $facultyLogo = faculty_logo($user->department); ?>
                <?php if ($facultyLogo !== null): ?>
                    <div class="user-avatar user-avatar--logo">
                        <img src="<?= e($facultyLogo) ?>" alt="<?= e($user->department) ?> logo" width="64" height="64">
                    </div>
                <?php else: ?>
                    <div class="user-avatar">
                        <?= e(strtoupper($user->firstName[0] . $user->lastName[0])) ?>
                    </div>
                <?php endif; ?>
                <div class="user-details">
                    <h3><?= e($user->getFullName()) ?></h3>
                    <div class="user-meta">
                        <span class="user-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <path d="M8 21h8M12 17v4"/>
                            </svg>
                            <span>Employee ID: <?= e($user->employeeId) ?></span>
                        </span>
                        <span class="user-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <span><?= e($user->email) ?></span>
                        </span>
                        <span class="user-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 12h.01M9 15h.01M15 9h.01M15 12h.01M15 15h.01"/>
                            </svg>
                            <span><?= e($user->department) ?></span>
                        </span>
                        <span class="user-meta-item">
                            <span class="badge badge-primary"><?= e($user->role->label()) ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">Quick Actions</h2>
        </div>
        <div class="card-body">
            <div class="flex gap-4 flex-wrap">
                <?php if ($stats["period"]): ?>
                    <a href="/evaluations.php" class="btn btn-primary">Go to Evaluations</a>
                <?php endif; ?>
                <a href="#" class="btn btn-outline">View Reports</a>
                <a href="#" class="btn btn-outline">Settings</a>
                <?php if ($user->role->canManageUsers()): ?>
                    <a href="/evaluations.php?action=admin" class="btn btn-outline">Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . "/../templates/layout.php";

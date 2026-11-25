<?php
$sidebarCollapsed = isset($_COOKIE["dorsu_sidebar_collapsed"]) && $_COOKIE["dorsu_sidebar_collapsed"] === "1";
$sidebarScript = basename($_SERVER["SCRIPT_NAME"] ?? "");
$sidebarLogo = faculty_logo($user->department);
?>
<aside id="sidebar" class="sidebar<?= $sidebarCollapsed ? " collapsed" : "" ?>" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img class="sidebar-logo-img" src="/assets/img/dorsu-logo.png" alt="Davao Oriental State University" width="32" height="32">
        <span class="sidebar-brand-label">DORSU Faculty Evaluation</span>
        <button type="button" id="sidebarToggle" class="sidebar-toggle" aria-label="<?= $sidebarCollapsed ? "Expand navigation" : "Collapse navigation" ?>" aria-expanded="<?= $sidebarCollapsed ? "false" : "true" ?>" aria-controls="sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="/dashboard.php" class="sidebar-nav-link<?= $sidebarScript === "dashboard.php" ? " active" : "" ?>" title="Dashboard"<?= $sidebarScript === "dashboard.php" ? ' aria-current="page"' : "" ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="/evaluations.php" class="sidebar-nav-link<?= $sidebarScript === "evaluations.php" ? " active" : "" ?>" title="Evaluations"<?= $sidebarScript === "evaluations.php" ? ' aria-current="page"' : "" ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Evaluations</span>
            </a>
        </li>
        <li>
            <a href="/evaluations.php" class="sidebar-nav-link" title="Reports">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                <span>Reports</span>
            </a>
        </li>
        <?php if ($user->role->canManageUsers()): ?>
        <li>
            <a href="/evaluations.php?action=admin" class="sidebar-nav-link" title="Admin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4z"/><path d="M9 12l2 2 4-4"/></svg>
                <span>Admin</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="#" class="sidebar-nav-link" title="Settings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 012-2 2 2 0 012 2h.09a1.65 1.65 0 001.51 1 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 001.82.33h.09a1.65 1.65 0 001-1.51V15a1.65 1.65 0 00-.33-1.82 1.65 1.65 0 00-1.82-.33z"/></svg>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-user">
        <div class="sidebar-user-row">
            <div class="sidebar-user-avatar">
                <?php if ($sidebarLogo !== null): ?>
                    <img class="sidebar-faculty-logo" src="<?= e($sidebarLogo) ?>" alt="<?= e($user->department) ?>" title="<?= e($user->department) ?>">
                <?php else: ?>
                    <span class="sidebar-user-initial"><?= e(strtoupper($user->firstName[0] . $user->lastName[0])) ?></span>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($user->getFullName()) ?></div>
                <div class="sidebar-user-role"><?= e($user->role->label()) ?></div>
            </div>
            <a href="/logout.php" class="sidebar-logout-btn" title="Logout" aria-label="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </div>
</aside>
<div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

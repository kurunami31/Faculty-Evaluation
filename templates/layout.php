<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DORSU Faculty Evaluation System">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';">
    <title><?= e($pageTitle ?? "Faculty Evaluation System") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=9">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body>
    <?php if (($showNavbar ?? false) && isset($user)): ?>
        <button type="button" id="sidebarMobileToggle" class="sidebar-mobile-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <?php require __DIR__ . "/partials/sidebar.php"; ?>
    <?php endif; ?>

    <main class="main-content">
        <?php require __DIR__ . "/partials/messages.php"; ?>
        
        <?php if (isset($content)): ?>
            <?= $content ?>
        <?php else: ?>
            <?= $slot ?? "" ?>
        <?php endif; ?>
    </main>

    <?php if (($showNavbar ?? false) && isset($user)): ?>
        <?php require __DIR__ . "/partials/footer.php"; ?>
    <?php endif; ?>

    <script src="/assets/js/main.js?v=8"></script>
</body>
</html>

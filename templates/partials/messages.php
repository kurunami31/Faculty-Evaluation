<?php if (hasFlash("success") || hasFlash("error") || hasFlash("warning") || hasFlash("info")): ?>
    <div class="flash-messages" aria-live="polite" aria-atomic="true">
        <?php foreach (flash("success") as $message): ?>
            <div class="flash flash-success" role="alert">
                <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span><?= e($message) ?></span>
            </div>
        <?php endforeach; ?>
        
        <?php foreach (flash("error") as $message): ?>
            <div class="flash flash-error" role="alert">
                <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <span><?= e($message) ?></span>
            </div>
        <?php endforeach; ?>
        
        <?php foreach (flash("warning") as $message): ?>
            <div class="flash flash-warning" role="alert">
                <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span><?= e($message) ?></span>
            </div>
        <?php endforeach; ?>
        
        <?php foreach (flash("info") as $message): ?>
            <div class="flash flash-info" role="alert">
                <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span><?= e($message) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
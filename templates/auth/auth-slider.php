<?php
/**
 * Split auth slider: sign in and registration in one panel.
 *
 * Expected variables:
 * - $mode          "login" | "register"
 * - $loginErrors   array
 * - $loginOld      array
 * - $registerErrors array
 * - $registerOld   array
 */
$isRegister = ($mode ?? "login") === "register";

// Department => logo map for the registration preview
$deptLogos = [];
foreach (departments() as $dept) {
    $logo = faculty_logo($dept);
    if ($logo !== null) {
        $deptLogos[$dept] = $logo;
    }
}
?>
<div class="auth-page">
    <div class="auth-slider<?= $isRegister ? " is-register" : "" ?>" id="authSlider" data-mode="<?= $isRegister ? "register" : "login" ?>">

        <!-- Sign in -->
        <section class="auth-panel auth-panel--login" id="panel-login" aria-labelledby="login-title" <?= $isRegister ? "inert" : "" ?>>
            <div class="auth-panel-inner">
                <div class="auth-mobile-brand">
                    <img src="/assets/img/dorsu-seal.png" alt="" width="36" height="36">
                    <span>DORSU Faculty Evaluation</span>
                </div>

                <header class="auth-head">
                    <p class="auth-kicker">Faculty Portal</p>
                    <h1 class="auth-title" id="login-title">Sign in</h1>
                    <p class="auth-subtitle">Access your evaluation dashboard with your university account.</p>
                </header>

                <?php if (isset($loginErrors["_csrf"])): ?>
                    <div class="flash flash-error" role="alert">
                        <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5M12 16h.01"/>
                        </svg>
                        <span><?= e($loginErrors["_csrf"]) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($loginErrors["_auth"])): ?>
                    <div class="flash flash-error" role="alert">
                        <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5M12 16h.01"/>
                        </svg>
                        <span><?= e($loginErrors["_auth"]) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/index.php" class="auth-form" novalidate>
                    <input type="hidden" name="action" value="login">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="login_email" class="form-label">Email address</label>
                        <input
                            type="email"
                            id="login_email"
                            name="email"
                            class="form-input <?= isset($loginErrors["email"]) ? "form-input-error" : "" ?>"
                            value="<?= e($loginOld["email"] ?? "") ?>"
                            placeholder="faculty@dorsu.edu.ph"
                            required
                            autocomplete="username"
                            aria-describedby="<?= isset($loginErrors["email"]) ? "login_email-error" : "" ?>"
                        >
                        <?php if (isset($loginErrors["email"])): ?>
                            <span id="login_email-error" class="form-error" role="alert"><?= e($loginErrors["email"]) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <div class="auth-label-row">
                            <label for="login_password" class="form-label">Password</label>
                            <a href="#" class="auth-inline-link">Forgot password?</a>
                        </div>
                        <div class="password-field">
                            <input
                                type="password"
                                id="login_password"
                                name="password"
                                class="form-input <?= isset($loginErrors["password"]) ? "form-input-error" : "" ?>"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                aria-describedby="<?= isset($loginErrors["password"]) ? "login_password-error" : "" ?>"
                            >
                            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" data-target="login_password">
                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="display:none;">
                                    <path d="M4 4l16 16"/>
                                    <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 8 10 8a18.4 18.4 0 0 1-3.16 4.19M6.61 6.61A18.4 18.4 0 0 0 2 12s3.5 8 10 8a9.1 9.1 0 0 0 4.06-.94"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (isset($loginErrors["password"])): ?>
                            <span id="login_password-error" class="form-error" role="alert"><?= e($loginErrors["password"]) ?></span>
                        <?php endif; ?>
                    </div>

                    <label class="checkbox-wrapper auth-remember">
                        <input type="checkbox" id="remember_me" name="remember_me" class="checkbox-input" <?= !empty($loginOld["remember_me"]) ? "checked" : "" ?>>
                        <span class="checkbox-label">Remember me</span>
                    </label>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <span>Sign in</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </form>

                <p class="auth-switch-line">
                    New to the portal?
                    <a href="/index.php?mode=register" data-auth-switch="register">Create an account</a>
                </p>
            </div>
        </section>

        <!-- Registration -->
        <section class="auth-panel auth-panel--register" id="panel-register" aria-labelledby="register-title" <?= $isRegister ? "" : "inert" ?>>
            <div class="auth-panel-inner">
                <div class="auth-mobile-brand">
                    <img src="/assets/img/dorsu-seal.png" alt="" width="36" height="36">
                    <span>DORSU Faculty Evaluation</span>
                </div>

                <header class="auth-head">
                    <p class="auth-kicker">Faculty Registration</p>
                    <h1 class="auth-title" id="register-title">Create account</h1>
                    <p class="auth-subtitle">Register with your DORSU employee details.</p>
                </header>

                <?php if (isset($registerErrors["_csrf"])): ?>
                    <div class="flash flash-error" role="alert">
                        <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5M12 16h.01"/>
                        </svg>
                        <span><?= e($registerErrors["_csrf"]) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($registerErrors["_auth"])): ?>
                    <div class="flash flash-error" role="alert">
                        <svg class="flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5M12 16h.01"/>
                        </svg>
                        <span><?= e($registerErrors["_auth"]) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/index.php" class="auth-form" novalidate id="registerForm">
                    <input type="hidden" name="action" value="register">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="employee_id" class="form-label">Employee ID</label>
                            <input
                                type="text"
                                id="employee_id"
                                name="employee_id"
                                class="form-input <?= isset($registerErrors["employee_id"]) ? "form-input-error" : "" ?>"
                                value="<?= e($registerOld["employee_id"] ?? "") ?>"
                                placeholder="2024-0001"
                                required
                                autocomplete="off"
                                inputmode="numeric"
                                pattern="\d{4}-\d{4}"
                                aria-describedby="<?= isset($registerErrors["employee_id"]) ? "employee_id-error" : "employee_id-hint" ?>"
                            >
                            <?php if (isset($registerErrors["employee_id"])): ?>
                                <span id="employee_id-error" class="form-error" role="alert"><?= e($registerErrors["employee_id"]) ?></span>
                            <?php else: ?>
                                <span id="employee_id-hint" class="form-hint">XXXX-YYYY</span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input <?= isset($registerErrors["email"]) ? "form-input-error" : "" ?>"
                                value="<?= e($registerOld["email"] ?? "") ?>"
                                placeholder="faculty@dorsu.edu.ph"
                                required
                                autocomplete="email"
                                aria-describedby="<?= isset($registerErrors["email"]) ? "email-error" : "email-hint" ?>"
                            >
                            <?php if (isset($registerErrors["email"])): ?>
                                <span id="email-error" class="form-error" role="alert"><?= e($registerErrors["email"]) ?></span>
                            <?php else: ?>
                                <span id="email-hint" class="form-hint">@dorsu.edu.ph only</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First name</label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                class="form-input <?= isset($registerErrors["first_name"]) ? "form-input-error" : "" ?>"
                                value="<?= e($registerOld["first_name"] ?? "") ?>"
                                placeholder="Juan"
                                required
                                autocomplete="given-name"
                                aria-describedby="<?= isset($registerErrors["first_name"]) ? "first_name-error" : "" ?>"
                            >
                            <?php if (isset($registerErrors["first_name"])): ?>
                                <span id="first_name-error" class="form-error" role="alert"><?= e($registerErrors["first_name"]) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">Last name</label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                class="form-input <?= isset($registerErrors["last_name"]) ? "form-input-error" : "" ?>"
                                value="<?= e($registerOld["last_name"] ?? "") ?>"
                                placeholder="Dela Cruz"
                                required
                                autocomplete="family-name"
                                aria-describedby="<?= isset($registerErrors["last_name"]) ? "last_name-error" : "" ?>"
                            >
                            <?php if (isset($registerErrors["last_name"])): ?>
                                <span id="last_name-error" class="form-error" role="alert"><?= e($registerErrors["last_name"]) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">Faculty / Department</label>
                        <div class="department-field">
                            <select
                                id="department"
                                name="department"
                                class="form-select <?= isset($registerErrors["department"]) ? "form-input-error" : "" ?>"
                                required
                                data-logos='<?= e(json_encode($deptLogos)) ?>'
                                aria-describedby="<?= isset($registerErrors["department"]) ? "department-error" : "" ?>"
                            >
                                <option value="">Select your faculty</option>
                                <?php foreach (departments() as $dept): ?>
                                    <option value="<?= e($dept) ?>" <?= (($registerOld["department"] ?? "") === $dept) ? "selected" : "" ?>><?= e($dept) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="faculty-logo-preview" class="faculty-logo-preview" hidden>
                                <img id="faculty-logo-img" src="" alt="" width="44" height="44">
                            </div>
                        </div>
                        <?php if (isset($registerErrors["department"])): ?>
                            <span id="department-error" class="form-error" role="alert"><?= e($registerErrors["department"]) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-field">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input <?= isset($registerErrors["password"]) ? "form-input-error" : "" ?>"
                                placeholder="At least 12 characters"
                                required
                                autocomplete="new-password"
                                aria-describedby="password-strength password-hint"
                            >
                            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" data-target="password">
                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="display:none;">
                                    <path d="M4 4l16 16"/>
                                    <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 8 10 8a18.4 18.4 0 0 1-3.16 4.19M6.61 6.61A18.4 18.4 0 0 0 2 12s3.5 8 10 8a9.1 9.1 0 0 0 4.06-.94"/>
                                </svg>
                            </button>
                        </div>
                        <div id="password-strength" class="password-strength" aria-live="polite">
                            <div class="strength-bar">
                                <div class="strength-fill" data-strength="0"></div>
                            </div>
                            <span class="strength-text">Strength: <span class="strength-label">Too weak</span></span>
                        </div>
                        <span id="password-hint" class="form-hint">12+ characters with upper, lower, number, and symbol.</span>
                        <?php if (isset($registerErrors["password"])): ?>
                            <div class="form-error" role="alert">
                                <?php foreach ($registerErrors["password"] as $err): ?>
                                    <span><?= e($err) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input <?= isset($registerErrors["confirm_password"]) ? "form-input-error" : "" ?>"
                            placeholder="Re-enter your password"
                            required
                            autocomplete="new-password"
                            aria-describedby="<?= isset($registerErrors["confirm_password"]) ? "confirm_password-error" : "" ?>"
                        >
                        <?php if (isset($registerErrors["confirm_password"])): ?>
                            <span id="confirm_password-error" class="form-error" role="alert"><?= e($registerErrors["confirm_password"]) ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <span>Create account</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </form>

                <p class="auth-switch-line">
                    Already registered?
                    <a href="/index.php" data-auth-switch="login">Sign in instead</a>
                </p>
            </div>
        </section>

        <!-- Brand rail -->
        <aside class="auth-brand">
            <div class="auth-brand-media">
                <img src="/assets/img/campus.jpg" alt="">
            </div>
            <div class="auth-brand-body">
                <img class="auth-brand-seal" src="/assets/img/dorsu-seal.png" alt="Davao Oriental State University seal" width="72" height="72">

                <div class="auth-brand-copy">
                    <p class="auth-brand-eyebrow">Davao Oriental State University</p>
                    <h2 class="auth-brand-title">Faculty Evaluation System</h2>
                    <p class="auth-brand-text">A secure, paperless workspace for faculty performance review and institutional reporting.</p>
                </div>

                <ul class="auth-brand-points">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                        <span>Verified university accounts</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="4" y="10" width="16" height="10" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <span>Encrypted credentials and sessions</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
                        </svg>
                        <span>Transparent evaluation records</span>
                    </li>
                </ul>

                <div class="auth-brand-faculties">
                    <p class="auth-brand-faculties-label">Covering the faculties of DORSU</p>
                    <div class="auth-brand-faculties-row">
                        <?php foreach (departments() as $dept): ?>
                            <?php $logo = faculty_logo($dept); ?>
                            <?php if ($logo !== null): ?>
                                <img src="<?= e($logo) ?>" alt="<?= e($dept) ?>" title="<?= e($dept) ?>" width="40" height="40" loading="lazy">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="auth-brand-actions">
                    <div class="auth-brand-action" data-brand-action="login">
                        <p>New faculty member?</p>
                        <a class="btn btn-on-brand" href="/index.php?mode=register" data-auth-switch="register">Create an account</a>
                    </div>
                    <div class="auth-brand-action" data-brand-action="register">
                        <p>Already registered?</p>
                        <a class="btn btn-on-brand" href="/index.php" data-auth-switch="login">Sign in instead</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

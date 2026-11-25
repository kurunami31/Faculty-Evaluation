/**
 * DORSU Faculty Evaluation System
 * Progressive enhancement for the auth forms.
 *
 * Everything here is optional: the server-side validation in
 * App\Shared\Validation and App\Auth\DTO remains the source of truth.
 */
(function () {
    "use strict";

    var STRENGTH_LABELS = [
        "Too weak",
        "Very weak",
        "Weak",
        "Fair",
        "Strong",
        "Very strong"
    ];

    /**
     * Score a password from 0 to 5 using the same rules the server enforces.
     */
    function scorePassword(value) {
        if (value.length === 0) {
            return 0;
        }

        var score = 0;

        if (value.length >= 12) {
            score += 1;
        }
        if (value.length >= 16) {
            score += 1;
        }
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
            score += 1;
        }
        if (/\d/.test(value)) {
            score += 1;
        }
        if (/[^A-Za-z0-9]/.test(value)) {
            score += 1;
        }

        // A short password can never score above "Weak", no matter how varied.
        if (value.length < 12) {
            score = Math.min(score, 2);
        }

        return score;
    }

    function initPasswordToggles() {
        var toggles = document.querySelectorAll(".password-toggle");

        Array.prototype.forEach.call(toggles, function (button) {
            var targetId = button.getAttribute("data-target");
            var input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            var eye = button.querySelector(".icon-eye");
            var eyeOff = button.querySelector(".icon-eye-off");

            button.addEventListener("click", function () {
                var show = input.type === "password";

                input.type = show ? "text" : "password";
                button.setAttribute("aria-pressed", show ? "true" : "false");
                button.setAttribute(
                    "aria-label",
                    show ? "Hide password" : "Show password"
                );

                if (eye && eyeOff) {
                    eye.style.display = show ? "none" : "";
                    eyeOff.style.display = show ? "" : "none";
                }

                input.focus();
            });
        });
    }

    function initStrengthMeter() {
        var input = document.getElementById("password");
        var meter = document.getElementById("password-strength");

        if (!input || !meter) {
            return;
        }

        var fill = meter.querySelector(".strength-fill");
        var label = meter.querySelector(".strength-label");

        if (!fill || !label) {
            return;
        }

        function update() {
            var score = scorePassword(input.value);

            fill.setAttribute("data-strength", String(score));
            label.textContent = STRENGTH_LABELS[score];
        }

        input.addEventListener("input", update);
        update();
    }

    function initConfirmPassword() {
        var password = document.getElementById("password");
        var confirm = document.getElementById("confirm_password");

        if (!password || !confirm) {
            return;
        }

        function check() {
            if (confirm.value === "") {
                confirm.setCustomValidity("");
                return;
            }

            var matches = confirm.value === password.value;

            confirm.setCustomValidity(
                matches ? "" : "Passwords do not match."
            );
        }

        password.addEventListener("input", check);
        confirm.addEventListener("input", check);
    }

    function initAuthSlider() {
        var slider = document.getElementById("authSlider");
        var loginPanel = document.getElementById("panel-login");
        var registerPanel = document.getElementById("panel-register");

        if (!slider || !loginPanel || !registerPanel) {
            return;
        }

        function applyMode(mode, moveFocus) {
            var isRegister = mode === "register";

            slider.classList.toggle("is-register", isRegister);
            slider.setAttribute("data-mode", mode);
            document.title = isRegister
                ? "Create Account - Faculty Evaluation System"
                : "Sign In - Faculty Evaluation System";

            loginPanel.toggleAttribute("inert", isRegister);
            registerPanel.toggleAttribute("inert", !isRegister);
            loginPanel.setAttribute("aria-hidden", isRegister ? "true" : "false");
            registerPanel.setAttribute("aria-hidden", isRegister ? "false" : "true");

            if (moveFocus) {
                window.setTimeout(function () {
                    var active = isRegister ? registerPanel : loginPanel;
                    var field = active.querySelector("input:not([type='hidden'])");

                    if (field) {
                        field.focus({ preventScroll: true });
                    }
                }, 420);
            }
        }

        function currentMode() {
            var mode = new URL(window.location.href).searchParams.get("mode");

            return mode === "register" ? "register" : "login";
        }

        applyMode(currentMode(), false);

        Array.prototype.forEach.call(
            document.querySelectorAll("[data-auth-switch]"),
            function (link) {
                link.addEventListener("click", function (event) {
                    if (event.metaKey || event.ctrlKey || event.shiftKey) {
                        return;
                    }

                    event.preventDefault();

                    var mode =
                        link.getAttribute("data-auth-switch") === "register"
                            ? "register"
                            : "login";

                    applyMode(mode, true);

                    var url = new URL(window.location.href);

                    if (mode === "register") {
                        url.searchParams.set("mode", "register");
                    } else {
                        url.searchParams.delete("mode");
                    }

                    window.history.pushState({ mode: mode }, "", url);
                });
            }
        );

        window.addEventListener("popstate", function () {
            applyMode(currentMode(), false);
        });
    }

    function initFlashDismiss() {
        var flashes = document.querySelectorAll(".flash");

        Array.prototype.forEach.call(flashes, function (flash) {
            window.setTimeout(function () {
                flash.style.transition = "opacity 400ms ease";
                flash.style.opacity = "0";

                window.setTimeout(function () {
                    if (flash.parentNode) {
                        flash.parentNode.removeChild(flash);
                    }
                }, 400);
            }, 8000);
        });
    }

    /* ---------- Faculty logo preview on department select ---------- */

    function initFacultyLogoPreview() {
        var select = document.getElementById("department");
        var preview = document.getElementById("faculty-logo-preview");
        var img = document.getElementById("faculty-logo-img");

        if (!select || !preview || !img) {
            return;
        }

        var logos = {};
        try {
            logos = JSON.parse(select.getAttribute("data-logos") || "{}");
        } catch (err) {
            logos = {};
        }

        function update() {
            var url = logos[select.value] || "";
            if (url) {
                img.src = url;
                img.alt = select.value + " logo";
                preview.hidden = false;
            } else {
                preview.hidden = true;
            }
        }

        select.addEventListener("change", update);
        update();
    }

    /* ---------- Modals (CSP-safe: no inline handlers) ---------- */

    function initModals() {
        Array.prototype.forEach.call(
            document.querySelectorAll("[data-modal-open]"),
            function (btn) {
                btn.addEventListener("click", function () {
                    var dialog = document.getElementById(btn.getAttribute("data-modal-open"));
                    if (dialog && typeof dialog.showModal === "function") {
                        dialog.showModal();
                    }
                });
            }
        );

        Array.prototype.forEach.call(
            document.querySelectorAll("[data-modal-close]"),
            function (btn) {
                btn.addEventListener("click", function () {
                    var dialog = btn.closest("dialog");
                    if (dialog) {
                        dialog.close();
                    }
                });
            }
        );
    }

    /* ---------- Confirm-before-submit forms ---------- */

    function initConfirmForms() {
        Array.prototype.forEach.call(
            document.querySelectorAll("form[data-confirm]"),
            function (form) {
                form.addEventListener("submit", function (event) {
                    if (!window.confirm(form.getAttribute("data-confirm"))) {
                        event.preventDefault();
                    }
                });
            }
        );
    }

    /* ---------- Sidebar (desktop collapse rail + mobile drawer) ---------- */

    function initSidebar() {
        var sidebar = document.getElementById("sidebar");
        if (!sidebar) {
            return;
        }

        var toggle = document.getElementById("sidebarToggle");
        var mobileToggle = document.getElementById("sidebarMobileToggle");
        var overlay = document.getElementById("sidebarOverlay");
        var mobileQuery = window.matchMedia("(max-width: 1024px)");

        function isMobile() {
            return mobileQuery.matches;
        }

        function setCollapsed(collapsed) {
            sidebar.classList.toggle("collapsed", collapsed);
            if (toggle) {
                toggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
                toggle.setAttribute("aria-label", collapsed ? "Expand navigation" : "Collapse navigation");
            }
            try {
                document.cookie = "dorsu_sidebar_collapsed=" + (collapsed ? "1" : "0") + "; path=/; max-age=31536000; SameSite=Lax";
            } catch (e) { /* ignore cookie errors */ }
        }

        function setOpen(open) {
            sidebar.classList.toggle("open", open);
            if (overlay) {
                overlay.classList.toggle("open", open);
            }
            if (mobileToggle) {
                mobileToggle.setAttribute("aria-expanded", open ? "true" : "false");
            }
            document.body.classList.toggle("sidebar-open", open);
        }

        function handleToggle() {
            if (isMobile()) {
                setOpen(!sidebar.classList.contains("open"));
            } else {
                setCollapsed(!sidebar.classList.contains("collapsed"));
            }
        }

        if (toggle) {
            toggle.addEventListener("click", handleToggle);
        }
        if (mobileToggle) {
            mobileToggle.addEventListener("click", handleToggle);
        }
        if (overlay) {
            overlay.addEventListener("click", function () {
                setOpen(false);
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && sidebar.classList.contains("open")) {
                setOpen(false);
            }
        });

        // Restore persisted desktop state
        try {
            var match = document.cookie.match(/(?:^|; )dorsu_sidebar_collapsed=([^;]+)/);
            if (match && match[1] === "1") {
                setCollapsed(true);
            }
        } catch (e) { /* ignore */ }

        // Leaving mobile closes the drawer so it never covers the page on resize
        var onBreakpointChange = function () {
            if (!isMobile()) {
                setOpen(false);
            }
        };
        if (typeof mobileQuery.addEventListener === "function") {
            mobileQuery.addEventListener("change", onBreakpointChange);
        } else if (typeof mobileQuery.addListener === "function") {
            mobileQuery.addListener(onBreakpointChange);
        }
    }

    /* ---------- Evaluation form (auto-save, draft, submit) ---------- */

    function initEvaluationForm() {
        var form = document.getElementById("evaluationForm");
        if (!form) {
            return;
        }

        var evaluationId = form.getAttribute("data-evaluation-id");
        var evaluateeId = form.getAttribute("data-evaluatee-id");
        var csrf = form.getAttribute("data-csrf");
        var saveUrl = form.getAttribute("data-save-url");
        var submitUrl = form.getAttribute("data-submit-url");
        var statusEl = document.getElementById("saveStatus");

        function setStatus(text) {
            if (statusEl) {
                statusEl.textContent = text;
            }
        }

        function saveResponse(criterionId, score, comments) {
            var data = new FormData();
            data.append("evaluation_id", evaluationId);
            data.append("criterion_id", criterionId);
            data.append("score", score);
            data.append("comments", comments);
            data.append("csrf_token", csrf);

            setStatus("Saving…");

            return fetch(saveUrl, {
                method: "POST",
                body: data,
                credentials: "same-origin"
            }).then(function (resp) {
                return resp.json().then(function (body) {
                    if (resp.ok && body.success) {
                        setStatus("Saved");
                    } else {
                        setStatus(body.message || "Save failed");
                    }
                    return body;
                });
            }).catch(function () {
                setStatus("Save failed (network)");
            });
        }

        function criterionIdFromName(name) {
            return name.replace(/^(score_|comments_)/, "");
        }

        function currentScore(criterionId) {
            var input = form.querySelector('input[name="score_' + criterionId + '"]:checked');
            return input ? parseInt(input.value, 10) : -1;
        }

        function currentComments(criterionId) {
            var area = form.querySelector('textarea[name="comments_' + criterionId + '"]');
            return area ? area.value : "";
        }

        // Auto-save score changes
        form.addEventListener("change", function (event) {
            var target = event.target;
            if (!target.name || target.name.indexOf("score_") !== 0) {
                return;
            }
            var criterionId = criterionIdFromName(target.name);
            var label = target.closest(".score-btn");
            if (label) {
                Array.prototype.forEach.call(
                    form.querySelectorAll(".score-btn"),
                    function (b) { b.classList.remove("active"); }
                );
                label.classList.add("active");
            }
            saveResponse(criterionId, parseInt(target.value, 10), currentComments(criterionId));
        });

        // Debounced auto-save of comments
        var commentTimer = null;
        form.addEventListener("input", function (event) {
            var target = event.target;
            if (!target.name || target.name.indexOf("comments_") !== 0) {
                return;
            }
            var criterionId = criterionIdFromName(target.name);
            var score = currentScore(criterionId);
            if (score < 0) {
                return;
            }
            window.clearTimeout(commentTimer);
            commentTimer = window.setTimeout(function () {
                saveResponse(criterionId, score, target.value);
            }, 600);
        });

        // Save draft: persist every scored criterion
        var saveBtn = form.querySelector('[data-action="save-draft"]');
        if (saveBtn) {
            saveBtn.addEventListener("click", function () {
                var radios = form.querySelectorAll('input[type="radio"]:checked');
                var chain = Promise.resolve();
                Array.prototype.forEach.call(radios, function (radio) {
                    var criterionId = criterionIdFromName(radio.name);
                    chain = chain.then(function () {
                        return saveResponse(criterionId, parseInt(radio.value, 10), currentComments(criterionId));
                    });
                });
                chain.then(function () {
                    setStatus("Draft saved.");
                });
            });
        }

        // Submit evaluation
        var submitBtn = form.querySelector('[data-action="submit-evaluation"]');
        if (submitBtn) {
            submitBtn.addEventListener("click", function () {
                if (!window.confirm("Submit this evaluation? You will not be able to edit it afterwards.")) {
                    return;
                }
                var data = new FormData();
                data.append("evaluation_id", evaluationId);
                data.append("evaluatee_id", evaluateeId);
                data.append("csrf_token", csrf);

                submitBtn.disabled = true;

                fetch(submitUrl, {
                    method: "POST",
                    body: data,
                    credentials: "same-origin"
                }).then(function (resp) {
                    return resp.json().then(function (body) {
                        return { ok: resp.ok, body: body };
                    });
                }).then(function (result) {
                    if (result.ok && result.body.success) {
                        window.location.href = result.body.redirect || "/evaluations.php?action=dashboard";
                    } else {
                        window.alert(result.body.message || "Failed to submit evaluation.");
                        if (result.body.redirect) {
                            window.location.href = result.body.redirect;
                        }
                        submitBtn.disabled = false;
                    }
                }).catch(function () {
                    window.alert("Submission error. Please try again.");
                    submitBtn.disabled = false;
                });
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        initPasswordToggles();
        initStrengthMeter();
        initConfirmPassword();
        initAuthSlider();
        initFlashDismiss();
        initFacultyLogoPreview();
        initModals();
        initConfirmForms();
        initSidebar();
        initEvaluationForm();
    });
})();

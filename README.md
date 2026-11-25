# DORSU Faculty Evaluation System

Complete, secure Faculty Evaluation System for **Davao Oriental State University (DORSU)** â€” built in PHP 8.3+ with PDO + MySQL 8.0.

## Features

### Authentication
- **Double-slider sign-in/sign-up** on one page with DORSU seal + campus background
- Registration restricted to `@dorsu.edu.ph` emails
- Employee ID format `YYYY-NNNN` (e.g. `2024-0001`)
- Password policy: 12+ chars, upper/lower/digit/special
- bcrypt hashing (cost 12)
- CSRF synchronizer tokens on every form
- File-based rate limiting (5 attempts / 15 min)
- Account lockout after repeated failures
- Secure session cookies (HTTP-only, Secure, SameSite)
- Remember-me = stores email for form prefill only (does not extend session)
- Append-only audit log

### Evaluation Workflow
- Evaluation periods (create, activate/deactivate â€” admin/chair only)
- Evaluation criteria with weights and score ranges (admin/chair only)
- Faculty evaluate colleagues (one draft per evaluator+evaluatee per period)
- Slider-based score input + comments, auto-saved as draft
- Submit evaluation â†’ weighted total score computed
- Admin oversight: view all evaluations, change statuses (submitted/reviewed/approved)
- Role-based access: `admin`, `chair`, `faculty`

### UI / DORSU Branding
- DORSU seal + campus background on auth pages
- SVG icons only (no emojis), flat colors
- 7 DORSU faculty logos integrated throughout:
  - Faculty of Computing, Engineering, and Technology (FACET)
  - Faculty of Teacher Education (FTED)
  - Faculty of Criminal Justice Education (FCJE)
  - Faculty of Nursing and Allied Health Sciences (FNAHS)
  - Faculty of Agriculture and Life Sciences (FALS)
  - Faculty of Humanities, Social Sciences, and Communication (FHSC)
  - Faculty of Business and Management (FBM)
- Logos shown in auth brand rail, register department preview, profile card, and header user menu
- Collapsible left sidebar navigation with user name/role and logout on the top bar

## Requirements

- PHP 8.3+
- MySQL 8.0
- Composer

## Installation

1. Copy the project to your web root:
   ```bash
   cd faculty-evaluation
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure environment â€” create `.env`:
   ```env
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=faculty_evaluation
   DB_USERNAME=root
   DB_PASSWORD=your_secure_password_here

   SESSION_LIFETIME=120
   SESSION_REMEMBER_LIFETIME=10080
   SESSION_SECURE_COOKIE=false
   SESSION_HTTP_ONLY=true
   SESSION_SAME_SITE=lax

   BCRYPT_COST=12
   RATE_LIMIT_ATTEMPTS=5
   RATE_LIMIT_WINDOW=900
   ACCOUNT_LOCKOUT_ATTEMPTS=5
   ACCOUNT_LOCKOUT_DURATION=900

   ALLOWED_EMAIL_DOMAIN=dorsu.edu.ph

   DEPARTMENTS=Faculty of Computing, Engineering, and Technology|Faculty of Teacher Education|Faculty of Criminal Justice Education|Faculty of Nursing and Allied Health Sciences|Faculty of Agriculture and Life Sciences|Faculty of Humanities, Social Sciences, and Communication|Faculty of Business and Management

   MFA_ISSUER=DORSU
   MFA_WINDOW=30
   ```

   Note: DEPARTMENTS is pipe-separated (|) because faculty names contain commas.\n    **Never commit your .env file** — it is already in .gitignore.

4. Create the database:
   ```sql
   CREATE DATABASE faculty_evaluation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Run migrations:
   ```bash
   php database/migrate.php
   ```

6. Seed demo users (optional, for testing):
   - Register via the UI at `/index.php?mode=register`
   - Or promote a user to admin directly in MySQL:
     ```sql
     UPDATE users SET role='admin' WHERE email='admin@dorsu.edu.ph';
     ```

7. Start the dev server:
   ```bash
   php -S localhost:8000 -t public
   ```

   Visit `http://localhost:8000`

## Project Structure

```
faculty-evaluation/
â”œâ”€â”€ bootstrap.php              # App bootstrap + helpers (faculty_slug, faculty_logo, departments)
â”œâ”€â”€ composer.json
â”œâ”€â”€ .env                       # Environment config (not committed)
â”œâ”€â”€ .env.example
â”œâ”€â”€ config/
â”‚   â”œâ”€â”€ database.php           # PDO config (sqlite/mysql driver-keyed)
â”‚   â””â”€â”€ session.php            # Session configuration
â”œâ”€â”€ database/
â”‚   â”œâ”€â”€ migrate.php     # Driver-aware migration runner (auto-creates MySQL DB) (auto-creates MySQL DB)
â”‚   â””â”€â”€ migrations/
â”‚       â”œâ”€â”€ 001_create_users_table.php
â”‚       â””â”€â”€ 002_create_evaluation_tables.php
â”œâ”€â”€ public/                    # Document root
â”‚   â”œâ”€â”€ index.php              # Auth slider (login/register)
â”‚   â”œâ”€â”€ register.php           # Redirects to index.php?mode=register
â”‚   â”œâ”€â”€ dashboard.php          # Protected dashboard + profile
â”‚   â”œâ”€â”€ evaluations.php        # Evaluation controller (dashboard/fill/save/submit/admin)
â”‚   â”œâ”€â”€ logout.php
â”‚   â”œâ”€â”€ assets/
â”‚   â”‚   â”œâ”€â”€ css/style.css
â”‚   â”‚   â”œâ”€â”€ js/main.js
â”‚   â”‚   â””â”€â”€ img/
â”‚   â”‚       â”œâ”€â”€ dorsu-seal.png
â”‚   â”‚       â”œâ”€â”€ dorsu-logo.png
â”‚   â”‚       â”œâ”€â”€ campus.jpg
â”‚   â”‚       â””â”€â”€ faculties/     # 7 faculty logo PNGs (facet, fted, fcje, fnahs, fals, fhsc, fbm)
â”‚   â””â”€â”€ .htaccess
â”œâ”€â”€ src/
â”‚   â”œâ”€â”€ Auth/
â”‚   â”‚   â”œâ”€â”€ DTO/
â”‚   â”‚   â”œâ”€â”€ Entity/            # User, UserRole, UserStatus
â”‚   â”‚   â”œâ”€â”€ Repository/        # UserRepository (incl. listActiveUsers)
â”‚   â”‚   â”œâ”€â”€ Service/           # AuthService
â”‚   â”‚   â””â”€â”€ Middleware/        # AuthMiddleware, CsrfMiddleware
â”‚   â”œâ”€â”€ Evaluation/
â”‚   â”‚   â”œâ”€â”€ Entity/            # Evaluation, EvaluationPeriod, EvaluationStatus, EvaluationCriterion, EvaluationResponse
â”‚   â”‚   â”œâ”€â”€ Repository/        # EvaluationRepository
â”‚   â”‚   â””â”€â”€ Service/           # EvaluationService
â”‚   â””â”€â”€ Shared/
â”‚       â”œâ”€â”€ AuditLogger.php
â”‚       â”œâ”€â”€ Session.php
â”‚       â””â”€â”€ Validation.php
â”œâ”€â”€ tests/
â”‚   â”œâ”€â”€ Unit/
â”‚   â””â”€â”€ Security/
â”œâ”€â”€ templates/
â”‚   â”œâ”€â”€ layout.php             # Base layout with sidebar
â”‚   â”œâ”€â”€ partials/
â”‚   â”‚   â”œâ”€â”€ sidebar.php        # Collapsible left navigation (includes burger toggle, brand, user profile)
â”‚   â”‚   â”œâ”€â”€ sidebar.php        # Collapsible left navigation
â”‚   â”‚   â”œâ”€â”€ messages.php
â”‚   â”‚   â””â”€â”€ footer.php
â”‚   â”œâ”€â”€ auth/
â”‚   â”‚   â””â”€â”€ auth-slider.php    # Login/register slider with faculty logo strip
â”‚   â””â”€â”€ evaluation/
â”‚       â”œâ”€â”€ dashboard.php
â”‚       â”œâ”€â”€ fill.php
â”‚      â””â”€â”€ admin.php
â”œâ”€â”€ docs/
â”‚   â””â”€â”€ screenshots/           # Browser screenshots (gitignored — generate locally)
â””â”€â”€ phpunit.xml
```

## Running Tests

```bash
vendor\bin\phpunit --configuration phpunit.xml
```

Expected: `32 tests, 75 assertions, OK`

## Security

| Feature | Implementation |
|---------|----------------|
| Password Hashing | bcrypt (cost 12) via `password_hash()` |
| SQL Injection | Prepared statements (PDO) everywhere |
| XSS Prevention | `htmlspecialchars()` on all output |
| CSRF | Synchronizer token pattern |
| Rate Limiting | File-based, 5 req / 15 min |
| Account Lockout | 5 attempts, lockout duration |
| Session Security | HTTP-only, Secure, SameSite cookies |
| Security Headers | CSP, X-Frame-Options: DENY, X-Content-Type-Options, Referrer-Policy |
| Audit Log | Append-only log of auth + evaluation state changes |

## Screenshots

Screenshots are **not committed** (see .gitignore). Generate them locally:

- Lab 1 (auth): docs/screenshots/lab1-01-login.png, lab1-02-register.png
- Run the app and capture via Playwright or browser dev tools for other pages

## License

Proprietary - Davao Oriental State University





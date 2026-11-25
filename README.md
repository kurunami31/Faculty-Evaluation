# DORSU Faculty Evaluation System

Complete, secure Faculty Evaluation System for **Davao Oriental State University (DORSU)** — built in PHP 8.3+ with PDO + MySQL 8.0.

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
- Remember-me = extended session lifetime only
- Append-only audit log

### Evaluation Workflow
- Evaluation periods (create, activate/deactivate — admin/chair only)
- Evaluation criteria with weights and score ranges (admin/chair only)
- Faculty evaluate colleagues (one draft per evaluator+evaluatee per period)
- Slider-based score input + comments, auto-saved as draft
- Submit evaluation → weighted total score computed
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

3. Configure environment — create `.env`:
   ```env
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=faculty_evaluation
   DB_USERNAME=root
   DB_PASSWORD=dmsgreate08312001

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

   Note: `DEPARTMENTS` is pipe-separated (`|`) because faculty names contain commas.

4. Create the database:
   ```sql
   CREATE DATABASE faculty_evaluation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Run migrations:
   ```bash
   php database/run_migrations.php
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
├── bootstrap.php              # App bootstrap + helpers (faculty_slug, faculty_logo, departments)
├── composer.json
├── .env                       # Environment config (not committed)
├── .env.example
├── config/
│   ├── database.php           # PDO config (sqlite/mysql driver-keyed)
│   └── session.php            # Session configuration
├── database/
│   ├── run_migrations.php     # Driver-aware migration runner (auto-creates MySQL DB)
│   └── migrations/
│       ├── 001_create_users_table.php
│       └── 002_create_evaluation_tables.php
├── public/                    # Document root
│   ├── index.php              # Auth slider (login/register)
│   ├── register.php           # Redirects to index.php?mode=register
│   ├── dashboard.php          # Protected dashboard + profile
│   ├── evaluations.php        # Evaluation controller (dashboard/fill/save/submit/admin)
│   ├── logout.php
│   ├── assets/
│   │   ├── css/style.css
│   │   ├── js/main.js
│   │   └── img/
│   │       ├── dorsu-seal.png
│   │       ├── dorsu-logo.png
│   │       ├── campus.jpg
│   │       └── faculties/     # 7 faculty logo PNGs (facet, fted, fcje, fnahs, fals, fhsc, fbm)
│   └── .htaccess
├── src/
│   ├── Auth/
│   │   ├── DTO/
│   │   ├── Entity/            # User, UserRole, UserStatus
│   │   ├── Repository/        # UserRepository (incl. listActiveUsers)
│   │   ├── Service/           # AuthService
│   │   └── Middleware/        # AuthMiddleware, CsrfMiddleware
│   ├── Evaluation/
│   │   ├── Entity/            # Evaluation, EvaluationPeriod, EvaluationStatus, EvaluationCriterion, EvaluationResponse
│   │   ├── Repository/        # EvaluationRepository
│   │   └── Service/           # EvaluationService
│   └── Shared/
│       ├── AuditLogger.php
│       ├── Session.php
│       └── Validation.php
├── tests/
│   ├── Unit/
│   └── Security/
├── templates/
│   ├── layout.php             # Base layout with sidebar
│   ├── partials/
│   │   ├── header.php         # Top bar (logo, user name/role, logout)
│   │   ├── sidebar.php        # Collapsible left navigation
│   │   ├── messages.php
│   │   └── footer.php
│   ├── auth/
│   │   └── auth-slider.php    # Login/register slider with faculty logo strip
│   └── evaluation/
│       ├── dashboard.php
│       ├── fill.php
│      └── admin.php
├── docs/
│   └── screenshots/           # Browser screenshots of each page
└── phpunit.xml
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

## Browser Screenshots

Located in `docs/screenshots/`:
- `v4-01-auth-login.png` — Sign-in slider
- `v4-02-auth-register.png` — Sign-up slider with department logo preview
- `v4-03-dashboard-sidebar.png` — Dashboard with collapsible sidebar
- `v4-04-evaluations-sidebar.png` — Evaluations dashboard (colleague cards with faculty logos)
- `v4-05-fill-sidebar.png` — Evaluation fill form (scores + comments)
- `v4-06-after-submit.png` — After submission
- `v4-07-admin-panel.png` — Admin panel (periods, criteria, oversight)

## License

Proprietary - Davao Oriental State University

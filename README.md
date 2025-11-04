# FitnessPro Backend

Backend API built with Laravel 12 that powers the FitnessPro platform (Angular SPA). This document is designed as a **guided tour** of the codebase: why technologies were selected, how requests flow, how data is persisted, and how to extend or operate the stack safely.

> Looking for the French version? See `READMEFR.md` in the same folder.

---

## 📚 Table of contents

1. [What lives in this backend?](#what-lives-in-this-backend)
2. [Technology stack & rationale](#technology-stack--rationale)
3. [Domain modules at a glance](#domain-modules-at-a-glance)
4. [Architecture & request flow](#architecture--request-flow)
5. [Data persistence & schema](#data-persistence--schema)
6. [External services & integrations](#external-services--integrations)
7. [Configuration & environment](#configuration--environment)
8. [Local development](#local-development)
9. [Database seeding (dev & prod)](#database-seeding-dev--prod)
10. [Authentication & security model](#authentication--security-model)
11. [API contract & error handling](#api-contract--error-handling)
12. [Logging, monitoring, background jobs](#logging-monitoring-background-jobs)
13. [Testing & quality gates](#testing--quality-gates)
14. [Contribution workflow](#contribution-workflow)
15. [Troubleshooting](#troubleshooting)

---

## What lives in this backend?

The Laravel API exposes everything the FitnessPro frontend needs:

- **User management** – registration/login, profile, password reset, auth tokens.
- **Workout engine** – templates, scheduled sessions, completed workouts, streaks.
- **Goal tracking** – smart goals with progress, achievements, status changes.
- **Nutrition assistant** – food database, calorie calculator, recommendations.
- **Calendar & notifications** – tasks, reminders, in-app + email notifications.
- **Analytics** – dashboards, stats services, streak calculators.

All features are structured so the business logic sits in dedicated services. This makes the codebase testable and maintainable when adding new domains (challenges, community feed, etc.).

---

## Technology stack & rationale

| Layer | Technology | Reason |
| --- | --- | --- |
| Runtime | **PHP 8.2** | Modern typing (readonly, enums), faster JIT, long-term support. |
| Framework | **Laravel 12** | Unified toolchain: routing, validation, Eloquent ORM, queues, notifications, mail. |
| Auth | **Laravel Sanctum** | Lightweight token auth designed for SPAs (Angular). |
| Database | **PostgreSQL (production)** | Reliable relational DB with JSONB support, indexing, window functions. |
| Dev DB | **SQLite** | Zero config, perfect for local development and tests. |
| Queues | **Database driver (default)** | Simple queue storage; can switch to Redis/SQS for scale. |
| Mail | **Laravel Notifications + Mailables** | Easy templating, queue integration, supports multiple channels. |
| Container | **Docker (optional)** | Sail-compatible; Render deployment uses PHP FPM + Nginx. |

> The stack intentionally favours boring, well-supported technologies that every PHP team knows how to run.

---

## Domain modules at a glance

| Module | Key files | What it does |
| --- | --- | --- |
| **Authentication** | `AuthController`, `AuthService`, `ForgotPasswordRequest`, `ResetPasswordNotification` | Login, registration, token issuance, password reset (token & direct). |
| **Workouts** | `WorkoutController`, `WorkoutService`, `WorkoutRepository`, `Workout`, `WorkoutExercise` | Template management, execution logs, streak updates, portfolio seed. |
| **Goals** | `GoalController`, `GoalService`, `GoalRepository`, `Goal` | CRUD on smart goals, progress computation, completion/activation flows. |
| **Calendar** | `CalendarController`, `CalendarTask`, `CalendarService` | Calendar events, reminders linked to workouts/goals. |
| **Notifications** | `NotificationController`, `WorkoutNotificationService`, Laravel notifications | DB + mail notifications for workouts, achievements, password reset. |
| **Nutrition** | `NutritionController`, `NutritionService`, `food-database.ts` (front) | Food catalogue, calorie calculator, recommendations. |
| **Analytics** | `DashboardController`, `StatisticsService`, `StreakCalculatorService` | Dashboard KPIs, streaks, charts data aggregation. |
| **Middleware** | `WorkoutApiLogger`, `WorkoutApiRateLimit`, `ValidateWorkoutOwnership` | Logging, rate limits, ownership checks for secured resources. |
| **Seeders** | `ProductionSeeder`, `ExerciseSeeder`, `WorkoutPlansSeeder`, dev-seed routes | Populate exercises, workouts, nutrition, users for demo/production. |

Each module follows the same pattern: controller → service → repository/model → notification/jobs. Once you understand one module, the rest feel familiar.

---

## Architecture & request flow

### Layered layout

```
HTTP Request
   │
   ▼
routes/api.php
   │  maps verb + URI
   ▼
Controller (thin)
   │  validates request data (FormRequest)
   ▼
Service (business logic)
   │  orchestrates repositories, jobs, notifications
   ▼
Repository / Model
   │  executes database queries (Eloquent)
   ▼
Database (PostgreSQL / SQLite)
   │
   ▼
JSON response (BaseController helpers)
```

### Detailed sequence (example: complete a scheduled workout)

```
User taps "Mark Workout Complete" on Angular
       │
       ├──> Frontend calls POST /api/workouts/logs
       │
       ├──> WorkoutController@completeLog
       │        ├─ validates payload (WorkoutCompleteRequest)
       │        └─ calls WorkoutService::completeLog
       │
       ├──> WorkoutService
       │        ├─ loads workout + exercises via repository
       │        ├─ stores stats in workout_exercises pivot
       │        ├─ updates goals via GoalsService
       │        ├─ updates streak via StreakCalculatorService
       │        └─ dispatches notifications/jobs as needed
       │
       ├──> Repositories / Models run DB updates
       │
       ├──> Service returns DTO
       │
       └──> Controller wraps DTO with success JSON response
```

Every feature follows a similar sequence. Services compose other services, repositories, and notifications to keep controllers stupid-simple.

### Directory cheat sheet

```
app/
  Http/Controllers/     # Request entry points
  Http/Middleware/      # Request guards (logging, throttling…)
  Http/Requests/        # Validation + typed input
  Models/               # Eloquent entities & relationships
  Services/             # Business logic orchestrators
  Notifications/        # Email & in-app notifications
  Traits/               # Shared helpers (ApiResponseTrait, BelongsToUserTrait)
database/
  migrations/           # Schema history
  seeders/              # Demo + production seeders
routes/api.php          # Route definitions
config/                 # Auth, mail, cors, sanctum, queue, database config
tests/                  # PHPUnit tests (Feature + Unit)
```

---

## Data persistence & schema

### Core tables

| Table | Description | Notable columns |
| --- | --- | --- |
| `users` | Profiles, auth, fitness metadata | `name`, `email`, `password`, `height`, `weight`, `nutrition_profile` |
| `workouts` | Training templates & completed sessions | `user_id`, `name`, `is_template`, `completed_at`, `notes` |
| `workout_exercises` | Pivot storing exercise details per workout | `workout_id`, `exercise_id`, `sets`, `reps`, `weight`, `tempo`, `rest` |
| `exercises` | Master catalogue (seeded from ProductionSeeder) | `name`, `equipment`, `body_part`, `difficulty`, `video_url` |
| `goals` | SMART goals with progress tracking | `title`, `target_value`, `unit`, `status`, `progress_percentage`, `deadline` |
| `goal_histories` | Audit/history of progress updates | `goal_id`, `previous_progress`, `new_progress`, `note` |
| `calendar_tasks` | Scheduled workouts/challenges/nutrition reminders | `user_id`, `related_type`, `related_id`, `scheduled_for`, `status` |
| `notifications` | In-app notifications (Laravel notifications table) | `type`, `data`, `read_at` |
| `password_reset_tokens` | Laravel table for reset tokens | `email`, `token`, `created_at` |
| `personal_access_tokens` | Sanctum tokens | `tokenable_type`, `tokenable_id`, `name`, `abilities`, `last_used_at` |

Secondary tables cover achievements, streaks, nutrition plans, articles, challenges, etc. The migrations folder documents each field precisely.

### Relationship diagram (simplified)

```
┌──────────────┐        ┌──────────────┐        ┌────────────────────┐
│    users     │ 1 ---->│   workouts   │ 1 ---->│ workout_exercises  │
└──────────────┘        └──────────────┘        └────────────────────┘
      │                        │                          │
      ▼                        │                          ▼
┌──────────────┐               │                ┌─────────────────┐
│    goals     │               │                │   exercises     │
└──────────────┘               │                └─────────────────┘
      │                        |
      ▼                        ▼
┌──────────────┐      ┌─────────────────┐      ┌──────────────────┐
│ notifications│      │ calendar_tasks  │      │ personal_tokens  │
└──────────────┘      └─────────────────┘      └──────────────────┘
```

### Data lifecycle highlights

- **Exercices & workouts** are seeded both in dev and production (`ProductionSeeder`).
- **Goals** record progress snapshots in history tables.
- **Password reset** tokens are stored in `password_reset_tokens`; direct reset updates hash & remember token.
- **Notifications** are stored both in DB (for in-app feed) and optionally mailed.
- **Jobs/events** can queue asynchronous tasks (sending notifications, heavy calculations). Queue driver defaults to the database for simplicity.

---

## External services & integrations

| Integration | Location | Why |
| --- | --- | --- |
| Mail (SMTP) | `config/mail.php`, `.env` | Sends password reset links, workout reminders. |
| Sanctum SPA Auth | `config/sanctum.php`, middleware `EnsureFrontendRequestsAreStateful` | Token-based auth without OAuth complexity. |
| Logger | `config/logging.php`, `WorkoutApiLogger` middleware | Structured logs for API calls, errors, business events. |
| Cache | `config/cache.php` (defaults to file) | Services like `StatisticsService` can cache heavy results. |
| Render / Docker | `Dockerfile`, `Procfile`, `fly.toml` | Production deployment on Render + optional Fly.io config. |
| Neon (PostgreSQL) | `config/database.php` | Managed Postgres for production; local uses SQLite. |
| Third-party APIs | `NutritionService` (if configured) | Example: integrate with external nutrition data providers. |

---

## Configuration & environment

Create `.env` from `.env.example` and set the following:

```env
APP_NAME=FitnessPro
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

FRONTEND_URL=http://localhost:4200
SANCTUM_STATEFUL_DOMAINS=localhost:4200
SESSION_DOMAIN=localhost

DB_CONNECTION=sqlite
DB_DATABASE=./database/database.sqlite

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fitnesspro.app
MAIL_FROM_NAME="FitnessPro"

RUN_DB_SEEDERS=false
DB_SEEDER_CLASS=ProductionSeeder
```

Production overrides (`APP_ENV=production`, `APP_DEBUG=false`, Postgres credentials, proper domains). Remember to set `FRONTEND_URL` to the deployed Angular host so reset links work.

---

## Local development

### Native (PHP locally installed)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# SQLite setup
touch database/database.sqlite
php artisan migrate

# Optional demo data
php artisan db:seed

php artisan serve          # http://localhost:8000
```

### Docker (Laravel Sail style)

1. Install Docker + Docker Compose.
2. Copy `.env.example` to `.env`, configure DB connection to `pgsql`.
3. Update `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` for the Sail containers.
4. Run `./vendor/bin/sail up` (after `composer install`).
5. API available at `http://localhost` with Postgres + Redis containers ready.

---

## Database seeding (dev & prod)

### Development helper endpoints

Available only when `APP_ENV=local`. Prefix: `/api/dev-seed`.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/api/dev-seed/portfolio` | Populate demos: users, workouts, exercises, goals, nutrition. |
| POST | `/api/dev-seed/run-migrations` | Run `php artisan migrate`. |
| POST | `/api/dev-seed/clear-exercises` | Truncate exercises. |
| POST | `/api/dev-seed/clear-workouts` | Truncate workouts & pivot rows. |

### Production seeding (Render + Neon)

1. Set env var `RUN_DB_SEEDERS=true` (keep `DB_SEEDER_CLASS=ProductionSeeder`).  
2. Redeploy; logs show `🌱 Running database seeders using ProductionSeeder...`.  
3. Reset `RUN_DB_SEEDERS=false` and redeploy to avoid seeding at every boot.

Manual seeding inside the container:

```bash
php artisan db:seed --force --no-interaction
```

When `APP_ENV=production`, this command runs only `ProductionSeeder` (exercises, nutrition, public workout templates). It does not alter user-generated data.

---

## Authentication & security model

### Sanctum SPA tokens

- SPA requests include the `X-XSRF-TOKEN` and Sanctum session cookie.
- For API tokens (mobile clients), use `personal_access_tokens` with ability scopes.
- Middleware `auth:sanctum` protects routes; `ValidateWorkoutOwnership` ensures resource ownership.

### Password reset flow

1. User triggers "forgot password" from the Angular login page.  
2. Frontend calls `POST /api/auth/password/email`.  
3. `AuthService::sendPasswordResetLink` issues a token, mails `ResetPasswordNotification`.  
4. Email link points to `${FRONTEND_URL}/reset-password?token=...&email=...`.  
5. Angular reset component prefills email, locks it, and calls either:  
   - `POST /api/auth/password/reset` (token path)  
   - `POST /api/auth/password/direct-reset` (fallback when no token).  
6. Backend updates the hashed password, regenerates `remember_token`, logs outcome.

### Additional protections

- Rate limiting via `ThrottleRequests` + custom `WorkoutApiRateLimit` middleware.
- CORS configured in `config/cors.php` to allow the Angular domain only.
- Sensitive logs are gated behind `config('app.debug')` to avoid leaking details in production.
- CSRF protection for SPA is handled by Sanctum’s stateful middleware.

---

## API contract & error handling

### Response structure

```jsonc
// success
{
  "success": true,
  "data": { ... },      // domain-specific payload
  "message": "Human readable message"
}

// error
{
  "success": false,
  "message": "What went wrong",
  "errors": {          // optional validation errors keyed by field
    "email": ["The email field is required."]
  }
}
```

- Errors inherit from `ApiResponseTrait` to keep structure consistent.
- Validation errors return HTTP 422 with field-level messages.
- Authentication errors return 401/403 with safe messages.
- Unexpected failures are logged and return 500 with generic `message` (detailed `debug` info only when `APP_DEBUG=true`).

### Pagination & filtering

- Most list endpoints use Laravel’s paginator: `data`, `meta`, `links`.  
- Filters accepted via query parameters (e.g. `GET /api/goals?status=active`).  
- Sorters are validated via FormRequests to prevent SQL injection.

---

## Logging, monitoring, background jobs

| Area | Implementation | Notes |
| --- | --- | --- |
| HTTP logs | `WorkoutApiLogger` middleware, Laravel channel `stack` | Logs method, URI, execution time, user ID. |
| Business events | Services log major state changes (goal status, password resets). |
| Error tracking | Logged to `storage/logs/laravel.log`; integrate with Sentry/Bugsnag if desired. |
| Queues | Default `database` queue; jobs stored in `jobs` table. Change to Redis/SQS for scale. |
| Scheduler | `app/Console/Kernel.php` can schedule commands (e.g., nightly summaries). |
| Notifications | Use mail + database channels; queue heavy emails to avoid delaying responses. |

To tail logs locally: `tail -f storage/logs/laravel.log`. In production (Render), view logs in the dashboard or attach external logging (Papertrail, Datadog). 

---

## Testing & quality gates

```bash
# entire suite
php artisan test

# specific feature
php artisan test tests/Feature/Auth/PasswordResetTest.php

# filtering by test case
php artisan test --filter=GoalsServiceTest
```

Recommended tooling:

- **PHPUnit** – integrated framework tests.  
- **Larastan / PHPStan** – static analysis (`./vendor/bin/phpstan analyse`).  
- **Laravel Pint** – code style fixes (`./vendor/bin/pint`).  
- **Pest** (optional) – alternative testing syntax if preferred.  
- **CI** – run tests + analysis on every PR before deploy.

Test philosophy:

- Controllers are smoke-tested (status codes, contracts).  
- Services have unit tests for business rules (goal completion, streak logic).  
- Repositories can be tested with in-memory SQLite.  
- Notifications can be asserted with Laravel’s `Notification::fake()`.  
- Seeders tested via snapshot tests to ensure catalogue integrity.

---

## Contribution workflow

1. Create a branch from `main`.  
2. Run `composer test` (or `php artisan test`) before committing.  
3. Update seeders/tests/docs if behaviour changes.  
4. Follow PSR-12 / Laravel Pint formatting.  
5. Submit PR with summary + testing evidence.  
6. Code review ensures services stay thin and controllers remain logic-free.  
7. Merge once CI passes and review approvals are complete.  

Tips:

- New endpoints should validate input via FormRequests, call services, and use `ApiResponseTrait`.  
- When adding tables, include migration, model, factory, seeder (if needed) and tests.  
- Update both README (EN/FR) when architecture or processes change.  
- Consider writing docs/diagram updates (Mermaid, ASCII) to keep onboarding easy.  

---

## Troubleshooting

| Symptom | Possible cause | Fix |
| --- | --- | --- |
| Password reset link opens Angular without token | `FRONTEND_URL` misconfigured or encoded incorrectly | Check `.env`, ensure URL matches deployed front (no trailing slash). |
| Password reset fails with “invalid token” | Token expired (default 60 minutes) or user changed email | Re-run `password/email` endpoint; ensure queues/mail are working. |
| SPA requests return 401 | Sanctum domains not configured | Set `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, clear cookies. |
| Mail not delivered | SMTP credentials wrong or port blocked | Verify `.env`, test with `php artisan tinker` sending a notification. |
| Seeds rerun on every deploy | `RUN_DB_SEEDERS` left to `true` | Reset env var to `false` after the first run. |
| Storage permission errors | Filesystem read-only | Ensure `storage/` and `bootstrap/cache` are writable. |
| Queue jobs stay pending | Queue worker not running | Start worker (`php artisan queue:work`) or check scheduler on production platform. |
| JSON errors missing details in prod | `APP_DEBUG=false` hides stack traces | Inspect logs (`storage/logs/laravel.log`) or use remote logging. |

---

## Need more references?

- Laravel official docs – https://laravel.com/docs  
- Laravel Sanctum – https://laravel.com/docs/sanctum  
- PostgreSQL docs – https://www.postgresql.org/docs/  
- Frontend (Angular) docs – `../frontend/README.md`  
- Diagram tooling – https://mermaid.js.org, https://asciiflow.com  

Happy shipping! 🚀

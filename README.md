# Vivaaha — Matrimony SaaS Platform

A privacy-first, multilingual, mobile-responsive **matrimony SaaS platform** built with
**Laravel 12 + Inertia.js + React (TypeScript)**. Designed for a single matrimony
business today and prepared for multi-tenant SaaS (company / branch isolation) tomorrow.

> This repository is being delivered in **phases**. This is **Phase 1 — Foundation**.
> See the [Roadmap](#roadmap) for what ships in later phases.

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 12, PHP 8.3+ |
| Frontend | React 19 + TypeScript, Inertia.js 2, Vite |
| Styling | Tailwind CSS 4, shadcn/ui, Lucide icons, dark mode |
| Auth | Session auth + Laravel Sanctum (API/mobile ready) |
| RBAC | spatie/laravel-permission |
| Auditing | spatie/laravel-activitylog + dedicated `audit_logs` |
| Charts / Tables / Forms | Recharts, TanStack Table, React Hook Form + Zod |
| Database | MySQL 8+ (production) · SQLite (local/dev) |
| Payments | Razorpay (Phase 4) |
| Search | Laravel Scout — database driver, Meilisearch optional (Phase 3) |
| i18n | English + Malayalam (extensible) |

---

## What is included in Phase 1

- **Project & design system** — Laravel 12 + Inertia + React TS, branded rose/gold theme, dark mode, English/Malayalam localization.
- **Multi-tenant foundation** — `companies` + `branches`, a `BelongsToCompany` global-scope trait, and a `TenantManager`.
- **RBAC** — 10 roles (Super Admin → Premium Member) and 38 granular permissions, enforced by middleware + policies.
- **Authentication** — register with email **and** mobile, login with **either**, email verification, **mobile OTP**, password strength rules, login/device history, consent tracking, account **deactivation** and grace-period **deletion request** workflow.
- **Base data model** — member profiles, privacy preferences, configurable master data (religions, castes, sub-castes, mother tongues, education, professions), a self-referencing location hierarchy, platform settings, and audit logs.
- **UI** — SEO-friendly public landing page, split-screen auth, member dashboard (profile completion + suggestions), and a permission-gated admin dashboard with live metrics + charts.
- **Seeders** — roles/permissions, India-focused master data (with Malayalam translations), default settings, and a demo tenant with one account per staff role + 12 verified members.

### Demo credentials (after seeding)

All demo accounts use the password **`password`**.

| Role | Email |
| --- | --- |
| Super Admin | `superadmin@example.com` |
| Platform Owner | `owner@example.com` |
| Admin | `admin@example.com` |
| Moderator | `moderator@example.com` |
| Verification Staff | `verifier@example.com` |
| Support | `support@example.com` |
| Finance | `finance@example.com` |
| Marketing | `marketing@example.com` |
| Member | `member1@example.com` … `member12@example.com` |

---

## Requirements

- PHP **8.3+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `gd`, `intl`, `zip`
- Composer 2.x
- Node.js 20+ and npm
- MySQL 8+ (or SQLite for local dev)
- (Optional) Redis 6+ for cache/queue/session/Horizon

---

## Local installation

```bash
# 1. Install PHP and JS dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
#    a) SQLite (quickest for local dev)
touch database/database.sqlite
#    b) or MySQL — set DB_CONNECTION=mysql and DB_* in .env, then create the schema:
#       mysql -u root -p -e "CREATE DATABASE matrimony CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrate + seed
php artisan migrate --seed

# 5. Storage symlink (for public assets/avatars)
php artisan storage:link

# 6. Run it
composer run dev      # serves PHP, queue, logs and Vite together
# — or in separate terminals —
php artisan serve
npm run dev
```

Visit **http://localhost:8000** (or the `php artisan serve` URL).

### Build for production

```bash
npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Background workers & scheduler

```bash
# Queue worker (emails, OTP, notifications, exports)
php artisan queue:work --tries=3 --backoff=10

# Scheduler (add this single cron entry on the server)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Redis & Horizon (optional, when available)

Set `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` in `.env`.
Horizon can be added with `composer require laravel/horizon && php artisan horizon:install`
and run via `php artisan horizon`.

> **Shared-hosting fallback:** if Redis / persistent workers are unavailable, keep
> `database` drivers and run the queue via the scheduler (`queue:work --stop-when-empty`
> is triggered on the cron tick). Scout stays on the `database` driver — no Meilisearch needed.

---

## Localization

- UI strings live in `lang/en.json` and `lang/ml.json`. Add a locale by creating
  `lang/{code}.json` and appending the code to `APP_SUPPORTED_LOCALES`.
- The active locale resolves from the user preference → session → app default
  (`App\Http\Middleware\SetLocale`) and is switchable from the header.

---

## Security & privacy

- CSRF protection, hashed passwords (bcrypt), strong password policy, login rate limiting.
- Email + mobile OTP verification; OTPs are stored **hashed**, single-use, time-boxed, attempt-limited.
- Consent is recorded immutably (`user_consents`) with version, IP, and user agent.
- Account **deactivation** and grace-period **deletion request** instead of instant hard delete.
- Sensitive staff actions recorded in `audit_logs` (never storing passwords, tokens, ID contents, or payment secrets).
- Private disk for documents with signed URLs (used from Phase 2 onwards).
- Money is stored as integers/decimals — **never floats**. Public records use ULIDs.

### Production hardening checklist

- Serve over **HTTPS only**; set `SESSION_SECURE_COOKIE=true` and `APP_DEBUG=false`.
- Configure a Content-Security-Policy and secure headers at the web-server/proxy layer.
- Back up the database daily and before every deploy; test restores.
- Rotate logs (`logrotate` or Laravel daily channel) and set `LOG_LEVEL=warning`.

---

## Deployment (cPanel / shared hosting)

1. Upload the project **outside** `public_html`, then point the domain's document root to the project's `public/` directory (or symlink its contents).
2. Set the correct `.env` (production DB, `APP_ENV=production`, `APP_DEBUG=false`).
3. Run `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build` (build locally and upload `public/build` if Node is unavailable on the host).
4. `php artisan migrate --force`, `php artisan storage:link`, and cache config/routes/views.
5. Add the scheduler cron entry (above). File permissions: `storage/` and `bootstrap/cache/` must be writable (`755`/`775`).

An example Apache `public/.htaccess` ships with Laravel; ensure `mod_rewrite` is enabled.

---

## Docker / Laravel Sail

```bash
php artisan sail:install        # choose mysql (+ redis, meilisearch as needed)
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev
```

---

## Testing

```bash
php artisan test
```

Tests grow with each phase (auth, verification, RBAC, matching, payments, API).

---

## Project structure (highlights)

```
app/
  Actions/Auth/RegisterMember.php         # registration orchestration (transaction)
  Enums/                                  # Gender, UserStatus, ProfileStatus
  Http/Controllers/{Admin,Member,Auth,Settings}
  Http/Middleware/{SetLocale,EnsureAccountIsActive,HandleInertiaRequests}
  Models/                                 # Company, Branch, User, MemberProfile, master data…
  Models/Concerns/{BelongsToCompany,IsMasterData}
  Policies/MemberProfilePolicy.php
  Services/{Otp,Sms,Audit,Profile}
  Support/Tenancy/TenantManager.php
database/{migrations,seeders,factories}
lang/{en,ml}.json
resources/js/{pages,layouts,components,hooks,lib}
routes/{web,auth,settings}.php
```

---

## Roadmap

| Phase | Scope |
| --- | --- |
| **1 — Foundation** ✅ | Setup, RBAC, auth, base data model, layouts, landing, admin/member dashboards, seeders |
| **2 — Profiles** ✅ | Profile wizard, KYC/verification, photo upload & moderation, privacy settings, admin profile management |
| **3 — Discovery** ✅ | Search, advanced filters, explainable match scoring, saved searches, recommendations, interests/shortlist/blocking/reports |
| **4 — Billing** ✅ | Subscription plans, Razorpay checkout + webhooks (signature + idempotency), invoices/receipts, coupons, refunds, revenue analytics |
| 5 — Comms & Ops | Notifications, optional messaging, admin reports/analytics, CMS/settings, REST API + docs, expanded tests, deployment configs |

---

_Compatibility scores in this platform are guidance only and never guarantee marital outcomes._

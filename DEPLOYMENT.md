# Deployment Guide

This document covers deploying the Matrimony SaaS platform to **cPanel/shared
hosting** and to **containerized/VPS** environments, plus the Razorpay, SMTP and
background-worker configuration.

---

## 0. This installation (matrimony.nokkoo.in)

Quick-start for the current cPanel target. The document root is the project
folder itself (`/home/uddjzwrz/matrimony.nokkoo.in`), so requests are forwarded
into `public/` by the root `.htaccess` shipped with this repo — **or**, better,
change the subdomain's document root in cPanel to
`/home/uddjzwrz/matrimony.nokkoo.in/public`.

```bash
cd /home/uddjzwrz/matrimony.nokkoo.in
git clone https://github.com/makemycctv-design/matrimony.git .

cp .env.example .env
# edit .env (see the values block below), then:
php artisan key:generate
bash deploy.sh                 # composer install + build + migrate + cache
php artisan db:seed --force    # optional: roles/permissions, master data, plans, CMS, demo
```

`.env` values for this host (do **not** commit this file):

```dotenv
APP_NAME=Vivaaha
APP_ENV=production
APP_DEBUG=false
APP_URL=https://matrimony.nokkoo.in

APP_LOCALE=en
APP_SUPPORTED_LOCALES=en,ml

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uddjzwrz_matrimony
DB_USERNAME=uddjzwrz_matrimony
DB_PASSWORD=your-db-password        # the password you created in cPanel

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp                    # configure your SMTP host
RAZORPAY_ENABLED=false              # set true + keys when going live
```

cPanel cron (scheduler):

```
* * * * * cd /home/uddjzwrz/matrimony.nokkoo.in && php artisan schedule:run >> /dev/null 2>&1
```

If Node is not available on the host, run `npm ci && npm run build` locally and
upload the generated `public/build` directory.

---

## 1. Requirements

- PHP **8.3+** with: `pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, curl, gd, intl, zip`
- MySQL **8+** (or MariaDB 10.6+)
- Composer 2, Node 20+ (for building assets)
- HTTPS certificate (mandatory in production)
- Optional: Redis 6+ (cache/session/queue/Horizon), Meilisearch (search at scale)

---

## 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Set at minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=matrimony
DB_USERNAME=youruser
DB_PASSWORD=yourpass

SESSION_SECURE_COOKIE=true
```

### Razorpay

```dotenv
RAZORPAY_ENABLED=true
RAZORPAY_KEY=rzp_live_xxx
RAZORPAY_SECRET=xxx
RAZORPAY_WEBHOOK_SECRET=xxx
```

Configure the webhook in the Razorpay dashboard pointing to
`https://yourdomain.com/webhooks/razorpay` and subscribe to `payment.captured`,
`payment.failed`, and `refund.processed`. Use the **same** webhook secret above.
When these keys are absent the platform runs a safe internal test gateway.

### SMTP

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.youresp.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
```

---

## 3. cPanel / shared hosting

1. Upload the project **above** `public_html` (e.g. `~/matrimony`).
2. Point the domain's document root to `~/matrimony/public`, or symlink:
   `ln -s ~/matrimony/public ~/public_html` (or copy `public/` contents and set
   the `index.php` paths accordingly).
3. Install dependencies (via SSH if available):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   If Node is unavailable on the host, run `npm ci && npm run build` locally and
   upload the generated `public/build` directory.
4. Create the MySQL database + user in cPanel, then:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=RolesAndPermissionsSeeder --force
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
5. **Cron (scheduler)** — add a single cron entry in cPanel:
   ```
   * * * * * cd /home/youruser/matrimony && php artisan schedule:run >> /dev/null 2>&1
   ```
6. **Queues** — if persistent workers aren't allowed, keep `QUEUE_CONNECTION=database`;
   the scheduler drains the queue on each tick. If workers are allowed, add:
   ```
   php artisan queue:work --tries=3 --max-time=3600
   ```

### File permissions

```bash
chmod -R 775 storage bootstrap/cache
```

### Apache `.htaccess`

Laravel ships `public/.htaccess`. Ensure `mod_rewrite` is enabled. Force HTTPS
at the server level or add a redirect rule.

---

## 4. Containers / VPS

A production `Dockerfile` (multi-stage: Node build → Composer → PHP-FPM + Nginx +
supervisor for queue & scheduler) and a `docker-compose.yml` (app + MySQL +
Redis) are included.

```bash
docker compose up -d --build
# first boot runs migrate + caches via docker/entrypoint.sh
docker compose exec app php artisan db:seed --force   # optional demo data
```

### Local development

```bash
composer run dev        # serves PHP, queue, logs and Vite together
# or Laravel Sail:
php artisan sail:install && ./vendor/bin/sail up -d
```

### Redis + Horizon (optional, at scale)

Set `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`, then:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon        # run under supervisor/systemd
```

### Search at scale (optional)

`SCOUT_DRIVER=database` needs nothing extra. For Meilisearch set
`SCOUT_DRIVER=meilisearch` and configure `MEILISEARCH_HOST`/`MEILISEARCH_KEY`.

---

## 5. Post-deploy checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, HTTPS enforced, secure cookies on
- [ ] Config/route/view caches built
- [ ] Scheduler cron installed; queue worker running (or DB queue via cron)
- [ ] Razorpay webhook configured + verified; SMTP sending
- [ ] `storage/` and `bootstrap/cache/` writable
- [ ] Database backups scheduled (see below) and restore tested
- [ ] Log level set to `warning`; log rotation configured

---

## 6. Backups, retention & logs

- **Database**: schedule `mysqldump` daily and before each deploy; store off-site.
  ```
  0 2 * * * mysqldump -u user -p'pass' matrimony | gzip > /backups/matrimony-$(date +\%F).sql.gz
  ```
- **Uploaded media**: back up `storage/app/secure` (private photos/documents).
- **Data retention**: account deletion honours a configurable grace period
  (`APP_DELETION_GRACE_DAYS`) before purge.
- **Logs**: use the Laravel `daily` channel or system `logrotate`; never log
  passwords, tokens, ID contents or payment secrets (enforced in code).

---

## 7. API

A versioned REST API is available under `/api/v1` (Sanctum bearer tokens).
Interactive documentation (Swagger UI) is served at **`/api/docs`** and the raw
OpenAPI spec at **`/api/v1/openapi.json`**.

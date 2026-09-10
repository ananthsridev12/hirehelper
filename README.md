# HireHelper

A home-services marketplace (Urban Company / Flipkart Home Services style)
built as plain PHP 8, no framework, no Composer, MySQL via PDO. Designed to
run on ordinary shared/cPanel hosting with no SSH, no build step, and no
`composer install` on the server.

## What it does

Customers browse service categories (AC repair, home cleaning, electrician,
plumber, salon, painting, etc.), pick a service, book it for a date/time slot
at a saved address, and pay the professional after the job. Admins verify
providers and assign bookings to a provider who serves that category.
Providers see their assigned jobs and move them through
assigned → in progress → completed. Customers can rate a completed booking.

**Roles**: `customer`, `provider`, `admin`.

## Local development

```bash
# 1. Create + seed a local database
mysql -u root -e "CREATE DATABASE hirehelper CHARACTER SET utf8mb4;"
mysql -u root hirehelper < database/schema.sql

# 2. Configure
cp config/config.sample.php config/config.php
# edit config/config.php with your local DB credentials

# 3. Run
php -S localhost:8000 -t public dev-router.php
```

Visit `http://localhost:8000`. A seed admin account is created by
`schema.sql`:

- Email: `admin@hirehelper.test`
- Password: `Admin@123`

Change this password (or the seed row) before deploying to production.

## Folder structure

```
app/
  Core/          Router, Database, Auth, Request, Response/View, Csrf, Config,
                 Str, Url, Flash, helpers.php -- the hand-rolled "framework"
  Models/        One class per table, thin PDO wrappers
  Controllers/   One class per resource/module (Admin/, Provider/ subfolders)
  Views/         Plain PHP templates, no template engine
routes.php       Route definitions
public/
  index.php      Front controller
  assets/        css/, js/ -- no build step
config/
  config.sample.php   Committed template
  config.php          Real config, git-ignored
database/
  schema.sql          Full schema + seed data
.htaccess is inside public/ (mod_rewrite -> public/index.php)
.cpanel.yml      cPanel Git Version Control deploy tasks
dev-router.php   Mimics the rewrite for `php -S` locally
```

## Deploying to shared/cPanel hosting

1. cPanel → **Git™ Version Control** → Create, cloning this repo into a
   *working* path (not the live document root), e.g. `/home/USERNAME/hirehelper`.
2. Set the domain's document root to `<that path>/public`.
3. Upload `config/config.php` by hand once (copy from `config.sample.php`
   and fill in the database credentials cPanel gave you under
   **MySQL Databases**).
4. Import `database/schema.sql` once via **phpMyAdmin → Import**.
5. Edit `.cpanel.yml` at the repo root: replace `USERNAME` and the path in
   `DEPLOYPATH` with your actual cPanel username and the working path from
   step 1.
6. From then on, every deploy is two clicks in cPanel: **Update from
   Remote**, then **Deploy HEAD Commit**.

Schema changes after launch go in `database/migrations/` as numbered `.sql`
files, run by hand via phpMyAdmin — deploys never touch the database or
`config/config.php` automatically.

## Data model

- `users` — customer / provider / admin, role-based
- `provider_profiles` — 1:1 extension of a provider user (bio, city, verification)
- `categories` — service categories
- `provider_categories` — which categories a provider is skilled in
- `services` — bookable line items under a category
- `addresses` — customer saved addresses
- `bookings` — the booking lifecycle: pending → assigned → in_progress → completed / cancelled
- `reviews` — one review per completed booking

## Security baseline

- Every query via PDO prepared statements.
- Every output escaped through the `e()` helper (`htmlspecialchars`).
- CSRF token on every POST form, verified server-side.
- Passwords hashed with `password_hash()` / verified with `password_verify()`.
- Session ID regenerated on login/logout.
- Role checks centralized in `BaseController::requireRole()`.

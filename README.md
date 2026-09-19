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

This site deploys at `hire.easi7.in`, cPanel user `de2shrnx`, where the
domain's **document root is fixed** at `/home1/de2shrnx/hire.easi7.in` (it
can't be pointed at a `public/` subfolder). So the deploy is split across
two directories:

- `/home1/de2shrnx/hire.easi7.in` — the live document root. Only the
  contents of `public/` (`index.php`, `.htaccess`, `assets/`) land here.
  Anything in this folder is directly downloadable over the web, so
  nothing else goes here.
- `/home1/de2shrnx/hirehelper-app` — a sibling folder *outside* the web
  root. `app/`, `database/` and `routes.php` deploy here, and
  `config/config.php` is uploaded here by hand. None of it is reachable
  by URL.

Setup, once:

1. cPanel → **Git™ Version Control** → Create, cloning this repo into a
   working path of your choice (e.g. `/home1/de2shrnx/repositories/hirehelper`
   — this is just where cPanel keeps the git checkout it deploys *from*;
   it's unrelated to the two paths above, which is what `.cpanel.yml`
   deploys *to*).
2. Create the MySQL database via cPanel → **MySQL Databases**, and a user
   with full privileges on it.
3. Upload `config/config.php` by hand once to
   `/home1/de2shrnx/hirehelper-app/config/config.php` (copy from
   `config/config.sample.php`, fill in the DB host/name/user/pass cPanel
   gave you). Git-ignored, untouched by every future deploy.
4. Upload `public/app-root.php` by hand once to
   `/home1/de2shrnx/hire.easi7.in/app-root.php` (copy from
   `public/app-root.sample.php` — it already points at
   `/home1/de2shrnx/hirehelper-app`, so no edit needed unless that path
   changes). Also git-ignored. This is how `index.php`, once it's the only
   thing living in the document root, knows where to find `app/`.
5. Import `database/schema.sql` once via **phpMyAdmin → Import**, on the
   database you just created.
6. Click **Deploy HEAD Commit** in cPanel. This runs `.cpanel.yml`, which
   creates `/home1/de2shrnx/hirehelper-app` and copies `app/`, `database/`
   and `routes.php` into it, then copies the contents of `public/` into
   the document root.

From then on, every deploy is two clicks in cPanel: **Update from
Remote**, then **Deploy HEAD Commit** — steps 2–5 are one-time.

If the cPanel account, domain, or either path ever changes, update
`DOCROOT`/`APPROOT` in `.cpanel.yml` and the path returned by
`app-root.php` to match.

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

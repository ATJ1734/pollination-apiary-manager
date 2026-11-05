# Pollination & Apiary Manager

Tracks a commercial beekeeper's hives, inspections, mite treatments, pollination
contracts, and hive placements, and answers the question that runs the
business: **which hives are strong and healthy enough to fill our upcoming
contracts, and where are we short?** That question is the [contract
planner](#the-contract-planner) — see DEMO.md for a guided walkthrough.

## Tech stack

PHP 8.4 (`declare(strict_types=1)` everywhere), MySQL 8.4, vanilla JavaScript
(ES modules, no build step), plain CSS with custom properties, Chart.js 4
(vendored for offline use), Composer for autoloading and PHPUnit only. No
front-end or back-end framework. See [DECISIONS.md](DECISIONS.md) for why.

## Setup

### Option A — Docker Compose (as specified)

```bash
cp .env.example .env
# edit .env: set DB_PASSWORD and DB_ROOT_PASSWORD to real values
docker compose up -d
docker compose exec app php bin/migrate
docker compose exec app php bin/seed
```

The app is then at **http://localhost:8080**.

> **Note on this repository's own history:** the machine this app was built on
> had no Docker installed, so the Dockerfile and docker-compose.yml below were
> written to the brief but not exercised here. Every command, test, and demo
> story in this repo was instead verified with Option B. If `docker compose up`
> doesn't work cleanly on your machine, that fallback is known-good — see
> PROGRESS.md for exactly what was run.

### Option B — PHP's built-in server + local MySQL (no Docker)

Needs PHP 8.4 with `pdo_mysql`, `mbstring`, `curl`, `zip`, `openssl` enabled,
Composer, and a MySQL 8.4 server you can create a database on.

```bash
cp .env.example .env
# edit .env: DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD for your MySQL

composer install
php bin/migrate
php bin/seed
php -S 127.0.0.1:8080 -t public
```

The app is then at **http://127.0.0.1:8080**.

If MySQL isn't available at all on your machine, stop here — this app is
written for MySQL 8.4 specifically (window functions in the contract planner
and dashboard queries) and does not run on SQLite/Postgres without changes.

## Commands

Run these inside the app container (`docker compose exec app <command>`) or
directly if you're using Option B.

| Command | What it does |
|---|---|
| `docker compose up -d` | Start the app at http://localhost:8080 |
| `php bin/migrate` | Apply any new migrations. Safe to run twice. |
| `php bin/seed` | Load demo data into an **empty** database. Refuses to run if `users` already has rows. |
| `php bin/demo-reset` | Drop all tables, migrate, and reseed. Run this before every presentation — it's deterministic, so the data is identical every time. |
| `composer test` | Run the PHPUnit suite (unit + feature tests, against a separate `_test` database). |
| `php bin/smoke` | Sign in as each of the 3 demo roles and request every page, failing on any unexpected HTTP status. Needs the app already running; set `SMOKE_BASE_URL` if it's not at the default `http://127.0.0.1:8080`. |

## Demo sign-ins

Seeded by `bin/seed` / `bin/demo-reset`. Passwords come from `.env`
(`DEMO_ADMIN_PASSWORD` etc.) — these are the values in `.env.example`:

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | `AdminDemo!2026` |
| Manager | manager@example.com | `ManagerDemo!2026` |
| Field tech | fieldtech@example.com | `FieldDemo!2026` |

## Rebranding

Every company-identifying string in the app — name, tagline, address, phone,
email, website, logo path — comes from `config/company.php`, which reads
environment variables with bracketed placeholder defaults
(`[Company Name]`, etc.). To rebrand the whole app, edit `.env`:

```bash
COMPANY_NAME="Sunfield Apiaries"
COMPANY_TAGLINE="Pollination you can count on"
COMPANY_ADDRESS="118 Orchard Lane, Modesto, CA 95350"
COMPANY_PHONE="(209) 555-0142"
COMPANY_EMAIL="info@sunfieldapiaries.com"
COMPANY_WEBSITE="www.sunfieldapiaries.com"
COMPANY_LOGO=/assets/img/logo-placeholder.svg
```

No code changes needed — the header, sign-in page, footer, and the printed
contract summary all read from this one place.

## Deploying to a shared PHP host

1. Point the host's document root at this project's `public/` folder — that's
   the only folder meant to be web-accessible. Everything in `src/`,
   `templates/`, `config/`, and `database/` should sit outside the web root,
   or be blocked from direct access if your host only lets you choose a
   subfolder (the `.htaccess` in `public/` doesn't protect files outside it).
2. Upload everything, run `composer install --no-dev` on the host (or upload
   a pre-built `vendor/` if the host has no shell access).
3. Create a MySQL 8.4 database and user through the host's control panel.
4. Create `.env` from `.env.example` with that database's credentials, a real
   `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, and your real company
   details. **Change the three `DEMO_*_PASSWORD` values from their documented
   defaults** — the app refuses to start in production otherwise (see
   `config/bootstrap.php`).
5. Run `php bin/migrate` once (via SSH, or a one-off script) to create the
   schema. Run `php bin/seed` only if you actually want the demo data on that
   host; otherwise create your first admin user directly in the `users` table
   with `password_hash()`.
6. Confirm the host serves HTTPS — session cookies are marked `Secure`
   automatically whenever `APP_URL` starts with `https://`.

The app writes nothing to local disk except PHP's own session files, so it
needs no writable directories beyond whatever your host already gives PHP for
sessions.

## Tests

```bash
composer test
```

Runs against a second database (`<DB_DATABASE>_test`), migrated automatically
by `tests/bootstrap.php`, so it never touches demo data. The bootstrap also
tries to `CREATE DATABASE IF NOT EXISTS` that test database for you; if your
DB user doesn't have that privilege (typical on a shared host), create
`<DB_DATABASE>_test` yourself once before running `composer test`.

## Project layout

- `public/` — the only web root (front controller, `.htaccess`, static assets, vendored Chart.js).
- `src/` — PHP source: `Routing`, `Controllers`, `Repositories` (all SQL lives here), `Validation`, `Auth`, `View`, `Support`.
- `templates/` — PHP view templates and one shared layout.
- `database/migrations/` — numbered `.sql` files; `database/seed.php` — demo data.
- `bin/` — `migrate`, `seed`, `demo-reset`, `smoke`.
- `tests/` — PHPUnit `Unit/` and `Feature/` tests.
- `config/` — `app.php`, `database.php`, `company.php`, `bootstrap.php` — every one reads from the environment.

See [PLAN.md](PLAN.md) for the full schema (with an ER diagram), route table,
and milestone checklist; [DECISIONS.md](DECISIONS.md) for notable choices and
why; and [PROGRESS.md](PROGRESS.md) for what's been verified and any known
gaps.

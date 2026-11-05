# PROGRESS.md

## Environment
- Build machine had no Docker, PHP, MySQL, or Composer. Installed PHP 8.4.27, MySQL 8.4.9,
  and Composer via `winget`/`getcomposer.org`, running MySQL as a plain user process (see
  DECISIONS.md). `docker-compose.yml` is written but untested on this machine.

## What works
- Milestone 1 (Plan): PLAN.md has the ER diagram, every table/rule/route/page from the brief.
- Milestone 2 (Skeleton): `php -S` front controller boots, session login/logout verified
  end-to-end with curl (admin signs in, sees the header/nav/role, signs out, unauthenticated
  dashboard redirects to /login). Header/footer show `[Company Name]` placeholder from `.env`.
- Milestone 3 (Database): `bin/migrate` applies 9 migrations and is safe to run twice.
  `bin/seed` is deterministic (fixed `mt_srand(42)`) and prints row counts. All four planted
  stories verified by hand-written SQL: rising mite trend on apiary "Clearwater Bend",
  planted almond contract (60 required, 45 placed, 13 eligible in the planner — fills most
  of the 15-hive shortfall but not all), exactly 4 hives overdue (>21 days), 16 treatments
  past `remove_by` with no removal logged (comfortably over the "at least two" requirement).

## Bugs found and fixed during milestone 3
- `Router::authorize()` treated the `'any'` role marker as "no login required" instead of
  "any authenticated role," so `/dashboard` was reachable while logged out. Fixed order of
  checks so `guest` routes are public, everything else requires a session first.
- `Authenticator`'s constructor used `Database::connection()` as a promoted-property default
  value, which PHP rejects (`Fatal error: Constant expression contains invalid operations`)
  since it isn't a compile-time constant. Fixed by defaulting to `null` and resolving inside
  the constructor body.
- The seed script's planted "contract shortfall" hive pool initially overlapped with the
  planted "rising mite trend" apiary's hives, so a later UPDATE silently overwrote the mite
  trend data. Fixed by excluding that apiary from the contract's hive pool.

- Milestone 4 (Core records): apiaries, hives, inspections, treatments have full
  list/search/filter/paginate/create/edit/delete, all verified over curl (CRUD, validation
  errors, delete, audit log rows). Growers CRUD was also built now (same pattern as apiaries)
  since the brief's milestone 5 needs it anyway and it cost nothing extra to do alongside.
  Hive detail page has the status/latest-inspection cards, a Chart.js mite-load trend line,
  the full inspection timeline, treatments, and placement history, with "Log inspection" /
  "Log treatment" quick links (hidden once a hive is dead/sold/combined). Chart.js 4.4.4 is
  vendored into `public/assets/vendor/` so the app works offline, per the brief.
  23 PHPUnit tests pass (`composer test`): Validator rules, and the "retired hives can't
  receive new inspections/treatments" rule both as a pure unit test (`HiveStatusTest`) and
  end-to-end against the database (`InspectionBusinessRuleTest`), plus the treatment
  date-ordering rule (`DateRulesTest`). Live search confirmed to also work via a plain GET
  (no-JS fallback), since the search box sits inside a `<form method="get">`.

- Milestone 5 (Contracts): contracts CRUD, contract detail page (fill status, placed-hives
  list with remove), and the planner are all working. Verified over curl against the planted
  shortfall contract: 60 required, 45 placed, 13 eligible (correctly shows the "won't fully
  close the gap" warning), assigning 2 eligible hives moved it to 47/60 with 11 eligible left.
  Confirmed the all-or-nothing rule directly: re-submitting an already-placed hive alongside
  a fresh eligible one rolled back both (fresh hive count stayed at 0 for that batch).
  Field tech role checked: `/contracts/{id}` hides "Rate per hive"/"Estimated total" entirely
  (not just CSS-hidden), `/contracts/{id}/planner`, `/growers`, and `/admin/users` all 403.
  27 PHPUnit tests pass total, including `PlacementBusinessRuleTest` covering the overlap
  rule (`hasOverlap`), the minimum-frames rule, and all-or-nothing `assignBulk`.
  Demo database reset back to its planted state afterward with `bin/demo-reset`.

## What doesn't / known gaps
(updated as milestones complete)

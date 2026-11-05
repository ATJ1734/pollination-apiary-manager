# DECISIONS.md

One short entry per notable choice.

### Local run method: PHP built-in server + native MySQL, not Docker
No Docker Desktop, PHP, MySQL, or Composer were present on the build machine, and Docker
Desktop needs admin rights plus a large, slow install. The brief allows this fallback
explicitly ("If Docker isn't available where you run, use PHP's built-in server and a local
MySQL 8.4"). Installed PHP 8.4 and MySQL 8.4 Community via `winget`, and run `mysqld` as a
plain user process (not a Windows service, since that needs admin) with its data directory
under `.devdb/`. The `docker-compose.yml` / `Dockerfile` deliverables are still written per
section 4, for a host that does have Docker, but were not exercised on this machine.

### MITE_THRESHOLD and similar app settings live in `config/app.php`, not a database table
The brief calls it "a setting" but doesn't ask for an admin UI to edit it at runtime. A
database-backed settings table would add a model, a form, and a cache-invalidation question
for one integer. Reading it from an environment variable (`MITE_THRESHOLD`, default 3) matches
how every other configurable value in the app already works (`config/company.php`).

### Overlap and minimum-frames rules enforced in PHP inside a transaction, not as DB constraints
MySQL has no exclusion/range constraint like PostgreSQL's `EXCLUDE`. Enforcing "no overlapping
placement for a hive" purely in SQL would need a trigger, which is harder to read, test, and
explain in an interview than a repository method. `PlacementRepository::assertCanPlace()` runs
the overlap check and the minimum-frames check inside the same transaction as the insert, using
`SELECT ... FOR UPDATE` on the hive's existing placements to close the race window.

### One shared `CrudController` base for simple single-table entities
Apiaries and growers are plain single-table CRUD with no special business rules. Hives,
inspections, treatments, and contracts each have rules or cross-table joins the brief calls
out specifically, so they get their own controllers even though they reuse the same
`BaseRepository` (search/paginate/sort) underneath. This avoids writing the same list/search/
paginate code six times while keeping the parts that actually differ in plain, dedicated code.

### Session-based auth, roles checked in the router via a `roles` array per route
Every route declares which roles may hit it; `Router::dispatch()` checks the session role
before calling the controller, so a 403 happens even if a template forgets to hide a link —
the brief requires this ("Check the role on every route, not just by hiding buttons.").

### CSV export reuses each repository's list query, field techs get a column-filtered writer
Rather than a separate export-only query, `ExportController` calls the same repository method
list pages use, then strips money columns before writing CSV when the signed-in user is a
field tech — one place decides what a field tech may see, not two.

### No PDF generation; print page uses print CSS
The brief says "Do not generate PDFs on the server." `contracts/print.php` is a normal page
with a `@media print` stylesheet and no interactive chrome in print, so the browser's own
"Print to PDF" is the PDF path if anyone wants one.

# DEMO.md — 6-minute walkthrough

Run `php bin/demo-reset` right before presenting — it drops everything,
re-migrates, and reseeds with the same deterministic data every time, so this
script always matches what's on screen.

Sign-ins: **admin@example.com / AdminDemo!2026**, **fieldtech@example.com /
FieldDemo!2026** (passwords in `.env`).

## The four planted stories

This script is built around four things the seed data deliberately sets up
(see `database/seed.php` and the "planted contract" / "Story 1–4" comments in
it). After `bin/demo-reset`, you can always expect:

1. **Apiary "Clearwater Bend"** shows mite loads climbing over the last 3 months.
2. **One almond contract** (Valley Gold Almonds) starts in ~30 days, needs 60
   hives, has 45 already placed, and the planner can find only 13 more
   eligible hives — enough to close most of the 15-hive gap, not all of it.
3. **Exactly 4 hives** haven't been inspected in over 21 days.
4. **Several treatments** are past their `remove_by` date (at least 2 are
   planted on purpose; more often show up from the random 18 months of
   history — either way, the dashboard should never show zero).

## Script

**0:00 — Sign in as admin.** Point out the header shows the company name and
logo from `config/company.php` / `.env` — editing `.env` rebrands the whole
app, nothing is hard-coded.

**0:30 — Dashboard.** Five tiles at a glance: active hives, hives over mite
threshold, hives not inspected in 21+ days (**4** — story 3), active
contracts, and the 60-day hive shortfall (**15** — story 2, same number
we'll see again in the planner). Scroll to the mite-load-by-apiary chart and
point at **Clearwater Bend**'s line climbing through the last few months —
story 1. Scroll to "Needs attention": the mite alerts list is dominated by
Clearwater Bend hives, matching the chart; below that, the overdue
inspections and overdue treatments lists.

**1:30 — A hive's story.** Click into one of the overdue Clearwater Bend
hives from the needs-attention panel. Show the status badge, the mite-load
trend chart on the hive itself, the full inspection timeline, and treatment
history. Mention the business rule here: a hive marked dead/sold/combined
can't receive new inspections or treatments — try "Log inspection" on an
active hive to show the mobile-friendly form (large tap targets, number
steppers, yes/no toggles, defaults to today and to you as inspector).

**2:30 — Contracts list → the planted contract.** Go to Contracts, open the
Valley Gold Almonds almond contract. Point out: starts in ~30 days, 60 hives
required, 45 already placed, so a shortfall of 15 shown right on the page.

**3:00 — The planner (the centerpiece).** Click "Planner." The eligibility
table lists only hives that are active, inspected in the last 21 days, meet
the contract's minimum frames of bees, and are at or below the mite
threshold — and aren't already committed elsewhere. The banner says plainly:
**only 13 eligible, but short by 15** — assigning all of them won't fully
close the gap. Check a few boxes (or "select all"), click "Assign selected,"
and show the fill count move and the warning shrink. Mention the rule behind
the button: the assignment is all-or-nothing inside one database
transaction — if any selected hive turns out to already be placed elsewhere,
*none* of them get assigned, not just the bad one.

**4:15 — Print view.** From the contract page, "Print summary" — company
letterhead, grower details, terms, the placed-hives table, a signature line,
laid out for US Letter.

**4:45 — Switch roles.** Sign out, sign in as `fieldtech@example.com`. Show
that Growers, the planner, and Admin are all gone from the nav (not just
hidden — hitting those URLs directly 403s). Open the same contract: no rate,
no dollar totals anywhere on the page.

**5:30 — CSV export.** Export any list (Hives, say) as CSV straight from the
toolbar — same filters as what's on screen.

**6:00 — Done.**

## Likely interview questions

**Q: Why no framework, and how is the code organized?**
A plain front controller (`public/index.php`) builds a small `Router` that
maps `method + path` to a controller action and checks the signed-in role
*before* calling it — so access control can't be bypassed by forgetting to
hide a button. Controllers talk to `Repositories` (one per table, all raw
SQL via PDO prepared statements, no ORM) and render PHP templates through a
tiny `View` helper. Apiaries and growers — plain single-table CRUD with no
special rules — share one `CrudController` base class; hives, inspections,
treatments, and contracts each get their own controller because the brief
calls out real business rules for them. See DECISIONS.md for the reasoning
behind each of these choices.

**Q: How do you guarantee the planner's business rules can't be bypassed by
a crafted request?**
`PlannerController::assign()` hands the selected hive IDs to
`PlacementRepository::assignBulk()`, which opens one database transaction,
re-checks *every* hive's status, latest inspection frame count, and date
overlap against existing placements — server-side, regardless of what the
planner's own eligibility list showed the user — and throws if any of them
fail. The whole batch commits together or not at all. `tests/Feature/
PlacementBusinessRuleTest.php` proves the overlap rule, the minimum-frames
rule, and that a bad hive in a batch rolls back a good one alongside it.

**Q: How do you keep a field tech from ever seeing a dollar amount?**
It's stripped server-side, not hidden with CSS: `ContractController` and
`ExportController` both `unset($row['rate_per_hive'])` for a field-tech
session before the data ever reaches a template or a CSV writer, so there's
no code path — page, print view, or export — where the number exists in the
response to strip in the first place. `bin/smoke` asserts this directly by
checking that the literal string "Rate per hive" never appears in a
field-tech response body, across every page that role can reach.

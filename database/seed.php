#!/usr/bin/env php
<?php

declare(strict_types=1);

// Demo data for the Pollination & Apiary Manager. Deterministic (fixed seed)
// and built relative to today, so it never looks stale and every run is the
// same. See DEMO.md for the four "planted stories" this script guarantees
// regardless of what the random pass produces.

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Support\Database;

mt_srand(42);

$pdo = Database::connection();

$existingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($existingUsers > 0) {
    echo "Database is not empty (users table has rows). Run bin/demo-reset instead.\n";
    exit(1);
}

$today = new DateTimeImmutable('today');

// ---------------------------------------------------------------- helpers --

function insertRow(PDO $pdo, string $table, array $data): int
{
    $columns = array_keys($data);
    $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', $placeholders),
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    return (int) $pdo->lastInsertId();
}

function d(DateTimeImmutable $date): string
{
    return $date->format('Y-m-d');
}

function jitter(int $min, int $max): int
{
    return mt_rand($min, $max);
}

/** Seasonal baseline frame count: builds up in spring, peaks summer, falls off in winter. */
function seasonalFramesBaseline(DateTimeImmutable $date): float
{
    $doy = (int) $date->format('z');

    return 11 + 5 * sin(2 * M_PI * ($doy - 80) / 365);
}

/** Seasonal mite baseline: climbs through late summer, per the brief. */
function seasonalMitesBaseline(DateTimeImmutable $date): float
{
    $doy = (int) $date->format('z');

    return 6 + 6 * sin(2 * M_PI * ($doy - 150) / 365);
}

function randomPhone(int $n): string
{
    return sprintf('555-01%02d', $n % 100);
}

echo "Seeding demo data...\n";
$pdo->beginTransaction();

// ------------------------------------------------------------------ users --

$passwords = [
    'admin' => getenv('DEMO_ADMIN_PASSWORD') ?: 'AdminDemo!2026',
    'manager' => getenv('DEMO_MANAGER_PASSWORD') ?: 'ManagerDemo!2026',
    'field_tech' => getenv('DEMO_FIELD_TECH_PASSWORD') ?: 'FieldDemo!2026',
];

$userIds = [];
foreach ([
    ['name' => 'Avery Holt', 'email' => 'admin@example.com', 'role' => 'admin'],
    ['name' => 'Jordan Reyes', 'email' => 'manager@example.com', 'role' => 'manager'],
    ['name' => 'Casey Nguyen', 'email' => 'fieldtech@example.com', 'role' => 'field_tech'],
] as $u) {
    $userIds[$u['role']] = insertRow($pdo, 'users', [
        'name' => $u['name'],
        'email' => $u['email'],
        'password_hash' => password_hash($passwords[$u['role']], PASSWORD_DEFAULT),
        'role' => $u['role'],
        'active' => 1,
        'failed_logins' => 0,
        'locked_until' => null,
    ]);
}
$inspectorIds = array_values($userIds);

// -------------------------------------------------------------- apiaries --

$apiaryDefs = [
    ['name' => 'Clearwater Bend', 'location_description' => 'River-bottom yard off County Rd 12', 'lat' => 38.5816, 'lng' => -121.4944, 'owner' => 'Hank Dillard'],
    ['name' => 'Sunflower Ridge', 'location_description' => 'Hilltop yard above the old orchard', 'lat' => 38.7296, 'lng' => -121.2864, 'owner' => 'Marie Castellano'],
    ['name' => 'Old Mill Creek', 'location_description' => 'Creek-side yard behind the grain mill', 'lat' => 38.4404, 'lng' => -121.3714, 'owner' => 'Tom Esparza'],
    ['name' => 'Pinecrest Hollow', 'location_description' => 'Shaded yard at the edge of the pines', 'lat' => 38.9120, 'lng' => -121.0558, 'owner' => 'Linda Oyelaran'],
    ['name' => 'Cedar Hollow Farm', 'location_description' => 'Pasture yard behind the cedar windbreak', 'lat' => 38.3312, 'lng' => -121.6490, 'owner' => 'Roy Fenwick'],
    ['name' => 'Wildflower Meadow', 'location_description' => 'South meadow yard near the irrigation canal', 'lat' => 38.6475, 'lng' => -121.8803, 'owner' => 'Priya Chandran'],
];

$apiaryIds = [];
foreach ($apiaryDefs as $i => $a) {
    $apiaryIds[] = insertRow($pdo, 'apiaries', [
        'name' => $a['name'],
        'location_description' => $a['location_description'],
        'latitude' => $a['lat'],
        'longitude' => $a['lng'],
        'landowner_name' => $a['owner'],
        'landowner_phone' => randomPhone($i + 1),
        'notes' => null,
        'active' => 1,
    ]);
}
// The apiary this run's "rising mite load" story is attached to (see planting below).
$risingMiteApiaryId = $apiaryIds[0];

// ------------------------------------------------------------------ hives --

$hiveTypes = ['Langstroth deep', 'Langstroth medium', '8-frame Langstroth', 'Nuc'];
$queenSources = ['Package - local supplier', 'Walk-away split', 'Purchased queen - breeder', 'Overwintered queen'];
$markColors = ['White', 'Yellow', 'Red', 'Green', 'Blue'];

$hives = []; // id => ['apiary_id' => , 'status' => , ...]
$hiveCounter = 0;

foreach ($apiaryIds as $apiaryId) {
    for ($i = 0; $i < 25; $i++) {
        $hiveCounter++;
        $roll = mt_rand(1, 100);
        $status = match (true) {
            $roll <= 3 => 'dead',
            $roll <= 6 => 'combined',
            $roll <= 9 => 'sold',
            $roll <= 14 => 'queenless',
            default => 'active',
        };

        $establishedOn = $today->modify('-' . mt_rand(60, 1100) . ' days');

        $hiveId = insertRow($pdo, 'hives', [
            'hive_code' => sprintf('H-%04d', $hiveCounter),
            'apiary_id' => $apiaryId,
            'status' => $status,
            'hive_type' => $hiveTypes[mt_rand(0, count($hiveTypes) - 1)],
            'queen_installed_on' => d($establishedOn->modify('+' . mt_rand(0, 30) . ' days')),
            'queen_source' => $queenSources[mt_rand(0, count($queenSources) - 1)],
            'queen_mark_color' => $markColors[mt_rand(0, count($markColors) - 1)],
            'established_on' => d($establishedOn),
            'notes' => null,
        ]);

        $hives[$hiveId] = ['apiary_id' => $apiaryId, 'status' => $status, 'established_on' => $establishedOn];
    }
}

// ------------------------------------------------------------- inspections --

/** Hives that can be inspected/treated/placed at all. */
$liveHiveIds = array_keys(array_filter($hives, static fn ($h) => !in_array($h['status'], ['dead', 'sold', 'combined'], true)));

// Four hives planted for story 3: "not inspected in over 21 days." Picked up
// front so their timeline generation below can skip the recent window.
$overdueHiveIds = array_slice($liveHiveIds, 0, 4);

$latestInspectionByHive = []; // hive_id => ['date' => DateTimeImmutable, 'frames' => int, 'mites' => ?int]
$allMiteReadings = []; // for the treatments pass: hive_id => list of ['date'=>, 'mites_per_300'=>]

$eighteenMonthsAgo = $today->modify('-18 months');
$historyCutoff = $today->modify('-22 days'); // historical cadence stops here; the "recent" pass below covers the rest

foreach ($liveHiveIds as $hiveId) {
    $hive = $hives[$hiveId];
    $isQueenless = $hive['status'] === 'queenless';
    $start = $hive['established_on'] > $eighteenMonthsAgo ? $hive['established_on'] : $eighteenMonthsAgo;

    $cursor = $start;
    while ($cursor <= $historyCutoff) {
        $month = (int) $cursor->format('n');
        $intervalDays = ($month >= 3 && $month <= 10) ? mt_rand(10, 21) : mt_rand(28, 45);

        $framesBaseline = seasonalFramesBaseline($cursor);
        if ($isQueenless) {
            $framesBaseline *= 0.7;
        }
        $frames = (int) max(2, min(20, round($framesBaseline + jitter(-2, 2))));
        $brood = max(0, $frames - mt_rand(1, 4));

        $mites = null;
        if (mt_rand(1, 100) > 30) {
            $mites = (int) max(0, min(40, round(seasonalMitesBaseline($cursor) + jitter(-3, 3))));
        }

        $queenSeen = $isQueenless ? 0 : (mt_rand(1, 100) <= 85 ? 1 : 0);
        $eggsSeen = $queenSeen && mt_rand(1, 100) <= 80 ? 1 : 0;

        insertRow($pdo, 'inspections', [
            'hive_id' => $hiveId,
            'inspected_on' => d($cursor),
            'inspector_user_id' => $inspectorIds[mt_rand(0, count($inspectorIds) - 1)],
            'frames_of_bees' => $frames,
            'brood_frames' => $brood,
            'queen_seen' => $queenSeen,
            'eggs_seen' => $eggsSeen,
            'temperament' => mt_rand(1, 5),
            'honey_stores' => mt_rand(1, 5),
            'mites_per_300' => $mites,
            'disease_notes' => null,
            'actions_taken' => null,
        ]);

        if ($mites !== null) {
            $allMiteReadings[$hiveId][] = ['date' => $cursor, 'mites_per_300' => $mites];
        }

        $cursor = $cursor->modify("+{$intervalDays} days");
    }

    // The "recent" inspection: guarantees every hive except the four overdue
    // ones has been inspected within the last 21 days, independent of season.
    if (in_array($hiveId, $overdueHiveIds, true)) {
        $recentDate = $today->modify('-' . mt_rand(25, 35) . ' days');
    } else {
        $recentDate = $today->modify('-' . mt_rand(1, 18) . ' days');
    }

    $framesBaseline = seasonalFramesBaseline($recentDate);
    if ($isQueenless) {
        $framesBaseline *= 0.7;
    }
    $frames = (int) max(2, min(20, round($framesBaseline + jitter(-2, 2))));
    $mites = (int) max(0, min(40, round(seasonalMitesBaseline($recentDate) + jitter(-3, 3))));
    $queenSeen = $isQueenless ? 0 : 1;

    insertRow($pdo, 'inspections', [
        'hive_id' => $hiveId,
        'inspected_on' => d($recentDate),
        'inspector_user_id' => $inspectorIds[mt_rand(0, count($inspectorIds) - 1)],
        'frames_of_bees' => $frames,
        'brood_frames' => max(0, $frames - mt_rand(1, 4)),
        'queen_seen' => $queenSeen,
        'eggs_seen' => $queenSeen && mt_rand(1, 100) <= 80 ? 1 : 0,
        'temperament' => mt_rand(1, 5),
        'honey_stores' => mt_rand(1, 5),
        'mites_per_300' => $mites,
        'disease_notes' => null,
        'actions_taken' => null,
    ]);

    $allMiteReadings[$hiveId][] = ['date' => $recentDate, 'mites_per_300' => $mites];
    $latestInspectionByHive[$hiveId] = ['date' => $recentDate, 'frames' => $frames, 'mites' => $mites];
}

// Story 1: one apiary's mite loads trend upward over the last 3 months.
// Overrides whatever the random pass produced for that apiary's hives.
$stmt = $pdo->prepare('SELECT id FROM hives WHERE apiary_id = ? AND status NOT IN ("dead","sold","combined")');
$stmt->execute([$risingMiteApiaryId]);
$risingApiaryHiveIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($risingApiaryHiveIds as $hiveId) {
    $stmt = $pdo->prepare(
        'SELECT id, inspected_on FROM inspections WHERE hive_id = ? AND inspected_on >= ? ORDER BY inspected_on'
    );
    $stmt->execute([$hiveId, d($today->modify('-3 months'))]);
    $rows = $stmt->fetchAll();
    $count = count($rows);
    if ($count === 0) {
        continue;
    }

    foreach ($rows as $i => $row) {
        $trendMites = (int) round(4 + (14 * $i / max(1, $count - 1)) + jitter(-1, 1));
        $pdo->prepare('UPDATE inspections SET mites_per_300 = ? WHERE id = ?')
            ->execute([$trendMites, $row['id']]);

        if ($i === $count - 1) {
            $latestInspectionByHive[$hiveId]['mites'] = $trendMites;
        }
    }
}

// ------------------------------------------------------------- treatments --

$products = ['Formic Pro', 'Apivar strips', 'Oxalic acid vapor', 'MiteAway Quick Strips'];
$doses = ['2 strips', '1 treatment', '4 strips', '1 vapor cycle'];
$miteThreshold = (float) getenv('MITE_THRESHOLD') ?: 3.0;

foreach ($allMiteReadings as $hiveId => $readings) {
    $lastTreatmentEnd = null;
    foreach ($readings as $reading) {
        $miteLoad = $reading['mites_per_300'] / 3;
        if ($miteLoad <= $miteThreshold) {
            continue;
        }
        if ($lastTreatmentEnd !== null && $reading['date'] < $lastTreatmentEnd) {
            continue; // already mid-treatment
        }

        $appliedOn = $reading['date']->modify('+' . mt_rand(2, 5) . ' days');
        if ($appliedOn > $today) {
            continue;
        }
        $removeBy = $appliedOn->modify('+' . mt_rand(42, 56) . ' days');
        $removedOn = null;
        if ($removeBy < $today->modify('-7 days')) {
            $removedOn = $removeBy->modify('-' . mt_rand(0, 5) . ' days');
        }

        insertRow($pdo, 'treatments', [
            'hive_id' => $hiveId,
            'product' => $products[mt_rand(0, count($products) - 1)],
            'applied_on' => d($appliedOn),
            'dose' => $doses[mt_rand(0, count($doses) - 1)],
            'remove_by' => d($removeBy),
            'removed_on' => $removedOn !== null ? d($removedOn) : null,
            'notes' => null,
        ]);

        $lastTreatmentEnd = $removeBy;
    }
}

// Story 4: guarantee at least two treatments past remove_by with no removal
// logged, regardless of what the random pass above produced.
$overdueTreatmentHiveIds = array_slice($liveHiveIds, 4, 2);
foreach ($overdueTreatmentHiveIds as $hiveId) {
    $removeBy = $today->modify('-' . mt_rand(3, 6) . ' days');
    $appliedOn = $removeBy->modify('-49 days');
    insertRow($pdo, 'treatments', [
        'hive_id' => $hiveId,
        'product' => 'Apivar strips',
        'applied_on' => d($appliedOn),
        'dose' => '4 strips',
        'remove_by' => d($removeBy),
        'removed_on' => null,
        'notes' => 'Planted demo story: overdue removal.',
    ]);
}

// -------------------------------------------------------------- growers --

$growerDefs = [
    ['farm' => 'Valley Gold Almonds', 'contact' => 'Dana Whitfield'],
    ['farm' => 'Rolling Hills Orchards', 'contact' => 'Marcus Webb'],
    ['farm' => 'Stonebridge Cherry Co.', 'contact' => 'Elena Josic'],
    ['farm' => 'Blue Fox Blueberry Farm', 'contact' => 'Grace Tillman'],
    ['farm' => 'Harvest Moon Almond Ranch', 'contact' => 'Ibrahim Saleh'],
    ['farm' => 'Red Barn Apple Orchard', 'contact' => 'Sophie Lindqvist'],
    ['farm' => 'Twin Creeks Cherry Growers', 'contact' => 'Oscar Beaumont'],
    ['farm' => 'Meadowlark Berry Co-op', 'contact' => 'Renee Falkowski'],
];

$growerIds = [];
foreach ($growerDefs as $i => $g) {
    $growerIds[] = insertRow($pdo, 'growers', [
        'farm_name' => $g['farm'],
        'contact_name' => $g['contact'],
        'phone' => randomPhone($i + 20),
        'email' => strtolower(str_replace(' ', '', explode(' ', $g['contact'])[0])) . '@' . strtolower(str_replace([' ', '.', ','], '', $g['farm'])) . '.example.com',
        'address' => (100 + $i * 10) . ' Orchard Rd, Harvest Valley, CA',
        'notes' => null,
    ]);
}

// -------------------------------------------------------- planted contract --
// Story 2: an almond contract starting in ~30 days, short by about 15 hives
// out of 60 required. 45 hives are pre-placed; 12 more are eligible in the
// planner (active, inspected within 21 days, enough frames, mites in range,
// no conflicting placement) -- enough to close most, not all, of the gap.

$plantedStart = $today->modify('+30 days');
$plantedEnd = $plantedStart->modify('+35 days');
$plantedContractId = insertRow($pdo, 'contracts', [
    'grower_id' => $growerIds[0],
    'crop' => 'almonds',
    'season_year' => (int) $plantedStart->format('Y'),
    'hives_required' => 60,
    'min_frames_of_bees' => 11,
    'rate_per_hive' => 195.00,
    'start_date' => d($plantedStart),
    'end_date' => d($plantedEnd),
    'status' => 'signed',
]);

$reserved = [];
// Exclude the rising-mite-trend apiary's hives entirely so story 2's writes
// to frames/mites on placed/eligible hives can never clobber story 1's trend.
$pool = array_values(array_filter(
    $liveHiveIds,
    static fn ($id) => $hives[$id]['status'] === 'active' && $hives[$id]['apiary_id'] !== $risingMiteApiaryId,
));

$placedHiveIds = array_slice($pool, 0, 45);
$eligibleHiveIds = array_slice($pool, 45, 12);
$reserved = array_merge($placedHiveIds, $eligibleHiveIds);

foreach ($placedHiveIds as $hiveId) {
    $frames = max(11, $latestInspectionByHive[$hiveId]['frames'] ?? 11);
    $pdo->prepare('UPDATE inspections SET frames_of_bees = ?, mites_per_300 = ? WHERE hive_id = ? ORDER BY inspected_on DESC LIMIT 1')
        ->execute([$frames, min(6, $latestInspectionByHive[$hiveId]['mites'] ?? 4), $hiveId]);

    insertRow($pdo, 'placements', [
        'contract_id' => $plantedContractId,
        'hive_id' => $hiveId,
        'placed_on' => d($today),
        'removed_on' => null,
        'frames_at_placement' => $frames,
    ]);
}

foreach ($eligibleHiveIds as $hiveId) {
    $frames = mt_rand(11, 14);
    $mites = mt_rand(3, 8);
    $pdo->prepare('UPDATE inspections SET frames_of_bees = ?, mites_per_300 = ?, inspected_on = ? WHERE hive_id = ? ORDER BY inspected_on DESC LIMIT 1')
        ->execute([$frames, $mites, d($today->modify('-' . mt_rand(1, 10) . ' days')), $hiveId]);
}

// ----------------------------------------------------- other contracts --

$crops = ['almonds', 'apples', 'cherries', 'blueberries'];
$generalPool = array_values(array_diff($liveHiveIds, $reserved));
$placementIntervals = []; // hive_id => list of [start, end-or-null]

function hiveIsFree(array $placementIntervals, int $hiveId, DateTimeImmutable $start, ?DateTimeImmutable $end): bool
{
    foreach ($placementIntervals[$hiveId] ?? [] as [$existingStart, $existingEnd]) {
        $existingEndBound = $existingEnd ?? new DateTimeImmutable('2100-01-01');
        $newEndBound = $end ?? new DateTimeImmutable('2100-01-01');
        if ($start <= $existingEndBound && $existingStart <= $newEndBound) {
            return false;
        }
    }

    return true;
}

for ($c = 0; $c < 11; $c++) {
    $monthsOffset = mt_rand(-14, 5);
    $startDate = $today->modify("{$monthsOffset} months")->modify(mt_rand(-10, 10) . ' days');
    $endDate = $startDate->modify('+' . mt_rand(21, 42) . ' days');

    $status = match (true) {
        $endDate < $today => (mt_rand(0, 1) ? 'completed' : 'invoiced'),
        $startDate <= $today && $endDate >= $today => 'active',
        $startDate > $today->modify('+45 days') => 'draft',
        default => 'signed',
    };

    $hivesRequired = mt_rand(15, 50);
    $minFrames = mt_rand(6, 9);

    $contractId = insertRow($pdo, 'contracts', [
        'grower_id' => $growerIds[mt_rand(0, count($growerIds) - 1)],
        'crop' => $crops[mt_rand(0, count($crops) - 1)],
        'season_year' => (int) $startDate->format('Y'),
        'hives_required' => $hivesRequired,
        'min_frames_of_bees' => $minFrames,
        'rate_per_hive' => mt_rand(150, 220),
        'start_date' => d($startDate),
        'end_date' => d($endDate),
        'status' => $status,
    ]);

    if (in_array($status, ['draft'], true)) {
        continue; // not yet placed
    }

    $removedOn = in_array($status, ['completed', 'invoiced'], true) ? $endDate : null;
    $placedCount = 0;
    foreach ($generalPool as $hiveId) {
        if ($placedCount >= $hivesRequired) {
            break;
        }
        if (!hiveIsFree($placementIntervals, $hiveId, $startDate, $removedOn ?? $endDate)) {
            continue;
        }

        insertRow($pdo, 'placements', [
            'contract_id' => $contractId,
            'hive_id' => $hiveId,
            'placed_on' => d($startDate),
            'removed_on' => $removedOn !== null ? d($removedOn) : null,
            'frames_at_placement' => $minFrames + mt_rand(0, 4),
        ]);

        $placementIntervals[$hiveId][] = [$startDate, $removedOn];
        $placedCount++;
    }
}

$pdo->commit();

// ------------------------------------------------------------------ totals --

foreach (['users', 'apiaries', 'hives', 'inspections', 'treatments', 'growers', 'contracts', 'placements'] as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    echo str_pad($table, 14) . ": {$count}\n";
}

echo "\nDemo sign-ins:\n";
echo "  admin@example.com / {$passwords['admin']}\n";
echo "  manager@example.com / {$passwords['manager']}\n";
echo "  fieldtech@example.com / {$passwords['field_tech']}\n";

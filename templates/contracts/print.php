<?php /** @var array $contract @var array $placements @var array $company */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contract summary &mdash; <?= e($company['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-sheet">
    <div class="print-letterhead">
        <div>
            <img src="<?= e($company['logo']) ?>" alt="" width="48" height="42">
            <div class="company-name"><?= e($company['name']) ?></div>
        </div>
        <div class="company-block">
            <?= e($company['address']) ?><br>
            <?= e($company['phone']) ?><br>
            <?= e($company['email']) ?> &middot; <?= e($company['website']) ?>
        </div>
    </div>

    <div class="print-section">
        <h2>Grower</h2>
        <p>
            <strong><?= e($contract['farm_name']) ?></strong><br>
            <?= e($contract['contact_name'] ?? '') ?><br>
            <?= e($contract['phone'] ?? '') ?> &middot; <?= e($contract['email'] ?? '') ?><br>
            <?= e($contract['address'] ?? '') ?>
        </p>
    </div>

    <div class="print-section">
        <h2>Contract terms</h2>
        <p>
            Crop: <strong><?= e(ucfirst($contract['crop'])) ?></strong> &middot; Season: <?= (int) $contract['season_year'] ?><br>
            Dates: <?= e($contract['start_date']) ?> &rarr; <?= e($contract['end_date']) ?><br>
            Hives required: <?= (int) $contract['hives_required'] ?> &middot;
            Minimum frames of bees: <?= (int) $contract['min_frames_of_bees'] ?><br>
            <?php if (isset($contract['rate_per_hive'])): ?>
                Rate per hive: <?= '$' . number_format((float) $contract['rate_per_hive'], 2) ?> &middot;
                Estimated total: <?= '$' . number_format((float) $contract['rate_per_hive'] * (int) $contract['hives_required'], 2) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="print-section">
        <h2>Placed hives (<?= count($placements) ?>)</h2>
        <table>
            <thead><tr><th>Hive</th><th>Apiary</th><th>Placed on</th><th>Frames at placement</th></tr></thead>
            <tbody>
            <?php foreach ($placements as $p): ?>
                <tr>
                    <td><?= e($p['hive_code']) ?></td>
                    <td><?= e($p['apiary_name']) ?></td>
                    <td><?= e($p['placed_on']) ?></td>
                    <td><?= (int) $p['frames_at_placement'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="signature-line">
        <div class="sig">Grower signature / date</div>
        <div class="sig"><?= e($company['name']) ?> signature / date</div>
    </div>
</div>
</body>
</html>

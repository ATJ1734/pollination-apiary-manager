<?php
/** @var array $hive @var array|null $latest @var array $inspections @var array $treatments
 *  @var array $placements @var float $miteThreshold @var array $chartLabels @var array $chartValues */

$statusClass = match ($hive['status']) {
    'active' => 'badge-success',
    'queenless' => 'badge-danger',
    default => 'badge-muted',
};
$latestMiteLoad = ($latest && $latest['mites_per_300'] !== null) ? round($latest['mites_per_300'] / 3, 1) : null;
$overThreshold = $latestMiteLoad !== null && $latestMiteLoad > $miteThreshold;
?>
<div class="page-header">
    <h1>
        Hive <?= e($hive['hive_code']) ?>
        <span class="badge <?= $statusClass ?>"><?= e(ucfirst($hive['status'])) ?></span>
        <?php if ($overThreshold): ?><span class="badge badge-danger">Over mite threshold</span><?php endif; ?>
    </h1>
    <div>
        <?php if (!in_array($hive['status'], ['dead', 'sold', 'combined'], true)): ?>
            <a class="btn btn-primary" href="/inspections/create?hive_id=<?= (int) $hive['id'] ?>">Log inspection</a>
            <a class="btn btn-secondary" href="/treatments/create?hive_id=<?= (int) $hive['id'] ?>">Log treatment</a>
        <?php endif; ?>
        <?php if (!is_field_tech()): ?>
            <a class="btn btn-secondary" href="/hives/<?= (int) $hive['id'] ?>/edit">Edit</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="/hives">Back to hives</a>
    </div>
</div>

<div class="charts-grid">
    <div class="card">
        <h2>Hive details</h2>
        <p><strong>Apiary:</strong> <?= e($hive['apiary_name']) ?></p>
        <p><strong>Type:</strong> <?= e($hive['hive_type'] ?? '—') ?></p>
        <p><strong>Established:</strong> <?= e($hive['established_on'] ?? '—') ?></p>
        <p><strong>Queen installed:</strong> <?= e($hive['queen_installed_on'] ?? '—') ?>
            <?php if ($hive['queen_mark_color']): ?>(<?= e($hive['queen_mark_color']) ?> mark)<?php endif; ?></p>
        <p><strong>Queen source:</strong> <?= e($hive['queen_source'] ?? '—') ?></p>
        <?php if ($hive['notes']): ?><p><strong>Notes:</strong> <?= e($hive['notes']) ?></p><?php endif; ?>
    </div>

    <div class="card">
        <h2>Latest inspection</h2>
        <?php if ($latest === null): ?>
            <p class="hint">No inspections logged yet.</p>
        <?php else: ?>
            <p><strong>Date:</strong> <?= e($latest['inspected_on']) ?></p>
            <p><strong>Frames of bees:</strong> <?= (int) $latest['frames_of_bees'] ?></p>
            <p><strong>Mite load:</strong> <?= $latestMiteLoad === null ? 'not measured' : "{$latestMiteLoad} / 100 bees" ?></p>
            <p><strong>Queen seen:</strong> <?= $latest['queen_seen'] ? 'Yes' : 'No' ?>
                &middot; <strong>Eggs seen:</strong> <?= $latest['eggs_seen'] ? 'Yes' : 'No' ?></p>
        <?php endif; ?>
    </div>

    <div class="card chart-card">
        <h2>Mite load trend (per 100 bees)</h2>
        <?php if ($chartLabels === []): ?>
            <p class="hint">Not enough inspection history yet.</p>
        <?php else: ?>
            <canvas data-chart="line" height="220"
                    data-labels='<?= e(json_encode($chartLabels)) ?>'
                    data-datasets='<?= e(json_encode([[
                        'label' => 'Mites per 100 bees',
                        'data' => $chartValues,
                        'borderColor' => '#B8860B',
                        'backgroundColor' => '#B8860B',
                        'tension' => 0.25,
                        'spanGaps' => true,
                    ]])) ?>'></canvas>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Inspection timeline</h2>
    <?php if ($inspections === []): ?>
        <p class="hint">No inspections yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Date</th><th>Inspector</th><th>Frames</th><th>Brood</th><th>Queen</th><th>Eggs</th><th>Mites/100</th><th>Temperament</th><th>Honey</th></tr>
                </thead>
                <tbody>
                <?php foreach ($inspections as $i): ?>
                    <tr>
                        <td><?= e($i['inspected_on']) ?></td>
                        <td><?= e($i['inspector_name']) ?></td>
                        <td><?= (int) $i['frames_of_bees'] ?></td>
                        <td><?= (int) $i['brood_frames'] ?></td>
                        <td><?= $i['queen_seen'] ? 'Yes' : 'No' ?></td>
                        <td><?= $i['eggs_seen'] ? 'Yes' : 'No' ?></td>
                        <td><?= $i['mites_per_300'] !== null ? round($i['mites_per_300'] / 3, 1) : '—' ?></td>
                        <td><?= (int) $i['temperament'] ?>/5</td>
                        <td><?= (int) $i['honey_stores'] ?>/5</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Treatments</h2>
    <?php if ($treatments === []): ?>
        <p class="hint">No treatments logged.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Product</th><th>Applied</th><th>Dose</th><th>Remove by</th><th>Removed</th></tr></thead>
                <tbody>
                <?php foreach ($treatments as $t): ?>
                    <?php $overdue = $t['removed_on'] === null && $t['remove_by'] !== null && $t['remove_by'] < date('Y-m-d'); ?>
                    <tr>
                        <td><?= e($t['product']) ?></td>
                        <td><?= e($t['applied_on']) ?></td>
                        <td><?= e($t['dose'] ?? '') ?></td>
                        <td><?= e($t['remove_by'] ?? '—') ?></td>
                        <td>
                            <?php if ($t['removed_on']): ?>
                                <?= e($t['removed_on']) ?>
                            <?php elseif ($overdue): ?>
                                <span class="badge badge-danger">Overdue</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Placement history</h2>
    <?php if ($placements === []): ?>
        <p class="hint">This hive hasn't been placed on a contract yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Grower</th><th>Crop / season</th><th>Placed</th><th>Removed</th><th>Frames at placement</th></tr></thead>
                <tbody>
                <?php foreach ($placements as $p): ?>
                    <tr>
                        <td><?= e($p['farm_name']) ?></td>
                        <td><?= e(ucfirst($p['crop'])) ?> <?= (int) $p['season_year'] ?></td>
                        <td><?= e($p['placed_on']) ?></td>
                        <td><?= e($p['removed_on'] ?? 'still placed') ?></td>
                        <td><?= (int) $p['frames_at_placement'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

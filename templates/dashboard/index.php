<?php
/** @var array $tiles @var array $miteAlerts @var array $overdueInspectionList @var array $overdueTreatments
 *  @var array $mitesChartLabels @var array $mitesChartDatasets @var array $colonyLabels @var array $colonyValues
 *  @var array $contractLabels @var array $contractRequired @var array $contractPlaced */
?>
<h1>Dashboard</h1>

<div class="tiles">
    <div class="tile">
        <div class="tile-value"><?= $tiles['activeHives'] ?></div>
        <div class="tile-label">Active hives</div>
    </div>
    <div class="tile <?= $tiles['overThreshold'] > 0 ? 'tile-alert' : '' ?>">
        <div class="tile-value"><?= $tiles['overThreshold'] ?></div>
        <div class="tile-label">Hives over mite threshold</div>
    </div>
    <div class="tile <?= $tiles['overdueInspections'] > 0 ? 'tile-alert' : '' ?>">
        <div class="tile-value"><?= $tiles['overdueInspections'] ?></div>
        <div class="tile-label">Not inspected in 21+ days</div>
    </div>
    <div class="tile">
        <div class="tile-value"><?= $tiles['activeContracts'] ?></div>
        <div class="tile-label">Active contracts</div>
    </div>
    <div class="tile <?= $tiles['shortfall60'] > 0 ? 'tile-alert' : '' ?>">
        <div class="tile-value"><?= $tiles['shortfall60'] ?></div>
        <div class="tile-label">Hive shortfall, next 60 days</div>
    </div>
</div>

<div class="charts-grid">
    <div class="card chart-card">
        <h2>Average mite load per apiary (last 12 months)</h2>
        <canvas data-chart="line" height="220"
                data-labels='<?= e(json_encode($mitesChartLabels)) ?>'
                data-datasets='<?= e(json_encode($mitesChartDatasets)) ?>'></canvas>
    </div>
    <div class="card chart-card">
        <h2>Colony strength spread</h2>
        <canvas data-chart="bar" height="220"
                data-labels='<?= e(json_encode($colonyLabels)) ?>'
                data-datasets='<?= e(json_encode([[
                    'label' => 'Hives',
                    'data' => $colonyValues,
                    'backgroundColor' => '#B8860B',
                ]])) ?>'></canvas>
    </div>
    <div class="card chart-card">
        <h2>Contracts: filled vs. required</h2>
        <canvas data-chart="bar" height="220"
                data-labels='<?= e(json_encode($contractLabels)) ?>'
                data-datasets='<?= e(json_encode([
                    ['label' => 'Required', 'data' => $contractRequired, 'backgroundColor' => '#E2D8BF'],
                    ['label' => 'Placed', 'data' => $contractPlaced, 'backgroundColor' => '#B8860B'],
                ])) ?>'></canvas>
    </div>
</div>

<div class="panel-attention">
    <h2>Needs attention</h2>
    <?php if ($miteAlerts === [] && $overdueInspectionList === [] && $overdueTreatments === []): ?>
        <p>Nothing needs attention right now.</p>
    <?php else: ?>
        <?php if ($miteAlerts !== []): ?>
            <h3>Mite alerts</h3>
            <ul>
                <?php foreach ($miteAlerts as $m): ?>
                    <li><a href="/hives/<?= (int) $m['hive_id'] ?>"><?= e($m['hive_code']) ?></a>
                        (<?= e($m['apiary_name']) ?>) &mdash; <?= round((float) $m['mite_load'], 1) ?> mites/100 bees</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($overdueInspectionList !== []): ?>
            <h3>Overdue inspections</h3>
            <ul>
                <?php foreach ($overdueInspectionList as $o): ?>
                    <li><a href="/hives/<?= (int) $o['hive_id'] ?>"><?= e($o['hive_code']) ?></a>
                        (<?= e($o['apiary_name']) ?>) &mdash;
                        <?= $o['days_overdue'] === null ? 'never inspected' : (int) $o['days_overdue'] . ' days since last inspection' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($overdueTreatments !== []): ?>
            <h3>Treatments past remove-by date</h3>
            <ul>
                <?php foreach ($overdueTreatments as $t): ?>
                    <li><?= e($t['hive_code']) ?> &mdash; <?= e($t['product']) ?> (due <?= e($t['remove_by']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>

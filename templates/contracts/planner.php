<?php /** @var array $contract @var int $placedCount @var int $shortfall @var array $eligible */ ?>
<div class="page-header">
    <h1>Planner &mdash; <?= e(ucfirst($contract['crop'])) ?> for <?= e($contract['farm_name']) ?> (<?= (int) $contract['season_year'] ?>)</h1>
    <a class="btn btn-secondary" href="/contracts/<?= (int) $contract['id'] ?>">Back to contract</a>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-value"><?= (int) $contract['hives_required'] ?></div>
        <div class="tile-label">Hives required</div>
    </div>
    <div class="tile">
        <div class="tile-value"><?= $placedCount ?></div>
        <div class="tile-label">Hives placed so far</div>
    </div>
    <div class="tile <?= $shortfall > 0 ? 'tile-alert' : '' ?>">
        <div class="tile-value"><?= $shortfall ?></div>
        <div class="tile-label">Shortfall</div>
    </div>
</div>

<div class="card">
    <h2>Eligible hives</h2>
    <p class="hint">
        Active, inspected within the last <?= (int) config('app.inspection_overdue_days') ?> days, at least
        <?= (int) $contract['min_frames_of_bees'] ?> frames of bees, and mite load at or below
        <?= (float) config('app.mite_threshold') ?> per 100 bees, with no conflicting placement.
    </p>

    <?php if ($eligible === []): ?>
        <div class="empty-state">
            <div class="hex"></div>
            <p>No eligible hives right now. Log fresh inspections or wait for mite loads to come down.</p>
        </div>
    <?php else: ?>
        <?php if (count($eligible) < $shortfall): ?>
            <div class="flash flash-error">
                Only <?= count($eligible) ?> hive<?= count($eligible) === 1 ? '' : 's' ?> eligible, but the contract is short by <?= $shortfall ?>.
                Assigning all of them won't fully close the gap &mdash; log more inspections or treat high-mite hives to open up more.
            </div>
        <?php endif; ?>

        <form method="post" action="/contracts/<?= (int) $contract['id'] ?>/planner/assign">
            <?= csrf_field() ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th><input type="checkbox" data-select-all="hive-checkbox" aria-label="Select all"></th>
                        <th>Hive</th><th>Apiary</th><th>Frames of bees</th><th>Mite load /100</th><th>Days since inspection</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eligible as $h): ?>
                        <tr>
                            <td><input type="checkbox" name="hive_ids[]" value="<?= (int) $h['hive_id'] ?>" class="hive-checkbox"></td>
                            <td><a href="/hives/<?= (int) $h['hive_id'] ?>"><?= e($h['hive_code']) ?></a></td>
                            <td><?= e($h['apiary_name']) ?></td>
                            <td><?= (int) $h['frames_of_bees'] ?></td>
                            <td><?= $h['mites_per_300'] !== null ? round($h['mites_per_300'] / 3, 1) : 'not measured' ?></td>
                            <td><?= (int) $h['days_since_inspection'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Assign selected</button>
            </div>
        </form>
    <?php endif; ?>
</div>

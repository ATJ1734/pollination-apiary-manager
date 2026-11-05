<?php /** @var array $contract @var array $placements @var int $placedCount */ ?>
<div class="page-header">
    <h1><?= e(ucfirst($contract['crop'])) ?> &mdash; <?= e($contract['farm_name']) ?> (<?= (int) $contract['season_year'] ?>)</h1>
    <div>
        <?php if (!is_field_tech()): ?>
            <a class="btn btn-primary" href="/contracts/<?= (int) $contract['id'] ?>/planner">Planner</a>
            <a class="btn btn-secondary" href="/contracts/<?= (int) $contract['id'] ?>/edit">Edit</a>
            <a class="btn btn-secondary" href="/contracts/<?= (int) $contract['id'] ?>/print" target="_blank">Print summary</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="/contracts">Back to contracts</a>
    </div>
</div>

<div class="charts-grid">
    <div class="card">
        <h2>Contract details</h2>
        <p><strong>Grower:</strong> <?= e($contract['farm_name']) ?></p>
        <p><strong>Contact:</strong> <?= e($contract['contact_name'] ?? '—') ?> &middot; <?= e($contract['phone'] ?? '') ?></p>
        <p><strong>Dates:</strong> <?= e($contract['start_date']) ?> &rarr; <?= e($contract['end_date']) ?></p>
        <p><strong>Minimum frames of bees:</strong> <?= (int) $contract['min_frames_of_bees'] ?></p>
        <?php if (!is_field_tech()): ?>
            <p><strong>Rate per hive:</strong> <?= money($contract['rate_per_hive']) ?></p>
            <p><strong>Estimated total:</strong> <?= money($contract['rate_per_hive'] * $contract['hives_required']) ?></p>
        <?php endif; ?>
        <p><strong>Status:</strong> <span class="badge"><?= e(ucfirst($contract['status'])) ?></span></p>
    </div>

    <div class="card">
        <h2>Fill status</h2>
        <p class="tile-value" style="font-size:2.5rem;"><?= $placedCount ?> / <?= (int) $contract['hives_required'] ?></p>
        <p class="tile-label">hives placed / required</p>
        <?php $shortfall = max(0, (int) $contract['hives_required'] - $placedCount); ?>
        <?php if ($shortfall > 0): ?>
            <p class="badge badge-danger">Short by <?= $shortfall ?> hive<?= $shortfall === 1 ? '' : 's' ?></p>
        <?php else: ?>
            <p class="badge badge-success">Fully placed</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Placed hives</h2>
    <?php if ($placements === []): ?>
        <p class="hint">No hives placed on this contract yet. Use the planner to assign some.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Hive</th><th>Apiary</th><th>Placed on</th><th>Frames at placement</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($placements as $p): ?>
                    <tr>
                        <td><a href="/hives/<?= (int) $p['hive_id'] ?>"><?= e($p['hive_code']) ?></a></td>
                        <td><?= e($p['apiary_name']) ?></td>
                        <td><?= e($p['placed_on']) ?></td>
                        <td><?= (int) $p['frames_at_placement'] ?></td>
                        <td>
                            <?php if (!is_field_tech()): ?>
                                <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                                   data-action="/placements/<?= (int) $p['id'] ?>/remove"
                                   data-confirm-message="Remove hive &quot;<?= e($p['hive_code']) ?>&quot; from this contract?">Remove</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

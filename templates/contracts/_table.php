<?php /** @var array $rows @var int $total @var int $page @var int $perPage */
$statusBadge = static function (string $status): string {
    $class = match ($status) {
        'active' => 'badge-success',
        'signed' => 'badge',
        'draft' => 'badge-muted',
        default => 'badge-muted',
    };

    return '<span class="badge ' . $class . '">' . e(ucfirst($status)) . '</span>';
};
?>
<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No contracts match your search yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Grower</th><th>Crop</th><th>Season</th><th>Dates</th><th>Hives placed / required</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="/contracts/<?= (int) $row['id'] ?>"><?= e($row['farm_name']) ?></a></td>
                    <td><?= e(ucfirst($row['crop'])) ?></td>
                    <td><?= (int) $row['season_year'] ?></td>
                    <td><?= e($row['start_date']) ?> &rarr; <?= e($row['end_date']) ?></td>
                    <td><?= (int) $row['hives_placed'] ?> / <?= (int) $row['hives_required'] ?></td>
                    <td><?= $statusBadge($row['status']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-secondary" href="/contracts/<?= (int) $row['id'] ?>">View</a>
                        <?php if (!is_field_tech()): ?>
                            <a class="btn btn-sm btn-secondary" href="/contracts/<?= (int) $row['id'] ?>/planner">Planner</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

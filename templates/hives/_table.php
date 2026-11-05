<?php /** @var array $rows @var int $total @var int $page @var int $perPage */

$statusBadge = static function (string $status): string {
    $class = match ($status) {
        'active' => 'badge-success',
        'dead', 'combined', 'sold' => 'badge-muted',
        'queenless' => 'badge-danger',
        default => '',
    };

    return '<span class="badge ' . $class . '">' . e(ucfirst($status)) . '</span>';
};
?>
<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No hives match your filters yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Hive code</th><th>Apiary</th><th>Status</th><th>Type</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="/hives/<?= (int) $row['id'] ?>"><?= e($row['hive_code']) ?></a></td>
                    <td><?= e($row['apiary_name']) ?></td>
                    <td><?= $statusBadge($row['status']) ?></td>
                    <td><?= e($row['hive_type'] ?? '') ?></td>
                    <td>
                        <a class="btn btn-sm btn-secondary" href="/hives/<?= (int) $row['id'] ?>">View</a>
                        <?php if (!is_field_tech()): ?>
                            <a class="btn btn-sm btn-secondary" href="/hives/<?= (int) $row['id'] ?>/edit">Edit</a>
                            <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                               data-action="/hives/<?= (int) $row['id'] ?>/delete"
                               data-confirm-message="Delete hive &quot;<?= e($row['hive_code']) ?>&quot;? This cannot be undone.">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

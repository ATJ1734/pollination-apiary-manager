<?php /** @var array $rows @var int $total @var int $page @var int $perPage */ ?>
<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No treatments match your search yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Hive</th><th>Product</th><th>Applied</th><th>Remove by</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $overdue = $row['removed_on'] === null && $row['remove_by'] !== null && $row['remove_by'] < date('Y-m-d'); ?>
                <tr>
                    <td><a href="/hives/<?= (int) $row['hive_id'] ?>"><?= e($row['hive_code']) ?></a></td>
                    <td><?= e($row['product']) ?></td>
                    <td><?= e($row['applied_on']) ?></td>
                    <td><?= e($row['remove_by'] ?? '—') ?></td>
                    <td>
                        <?php if ($row['removed_on']): ?>
                            <span class="badge badge-success">Removed <?= e($row['removed_on']) ?></span>
                        <?php elseif ($overdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-secondary" href="/treatments/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <?php if (!is_field_tech()): ?>
                            <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                               data-action="/treatments/<?= (int) $row['id'] ?>/delete"
                               data-confirm-message="Delete this treatment record? This cannot be undone.">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

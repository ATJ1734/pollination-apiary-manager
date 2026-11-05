<?php /** @var array $rows @var int $total @var int $page @var int $perPage */ ?>
<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No growers match your search yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Farm</th><th>Contact</th><th>Phone</th><th>Email</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="/growers/<?= (int) $row['id'] ?>/edit"><?= e($row['farm_name']) ?></a></td>
                    <td><?= e($row['contact_name'] ?? '') ?></td>
                    <td><?= e($row['phone'] ?? '') ?></td>
                    <td><?= e($row['email'] ?? '') ?></td>
                    <td>
                        <a class="btn btn-sm btn-secondary" href="/growers/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                           data-action="/growers/<?= (int) $row['id'] ?>/delete"
                           data-confirm-message="Delete grower &quot;<?= e($row['farm_name']) ?>&quot;? This cannot be undone.">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

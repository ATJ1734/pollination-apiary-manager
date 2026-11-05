<?php /** @var array $rows @var int $total @var int $page @var int $perPage */ ?>
<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No apiaries match your search yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Location</th>
                <th>Landowner</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="/apiaries/<?= (int) $row['id'] ?>/edit"><?= e($row['name']) ?></a></td>
                    <td><?= e($row['location_description'] ?? '') ?></td>
                    <td><?= e($row['landowner_name'] ?? '') ?></td>
                    <td>
                        <?php if ($row['active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!is_field_tech()): ?>
                            <a class="btn btn-sm btn-secondary" href="/apiaries/<?= (int) $row['id'] ?>/edit">Edit</a>
                            <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                               data-action="/apiaries/<?= (int) $row['id'] ?>/delete"
                               data-confirm-message="Delete apiary &quot;<?= e($row['name']) ?>&quot;? This cannot be undone.">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

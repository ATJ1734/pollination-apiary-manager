<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q */ ?>
<div class="page-header">
    <h1>Users</h1>
    <a class="btn btn-primary" href="/admin/users/create">New user</a>
</div>

<div class="table-toolbar">
    <form method="get" action="/admin/users">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email...">
    </form>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No users match your search.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><span class="badge"><?= e(ucfirst(str_replace('_', ' ', $row['role']))) ?></span></td>
                    <td>
                        <?php if ($row['active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Disabled</span>
                        <?php endif; ?>
                        <?php if ($row['locked_until'] && $row['locked_until'] > date('Y-m-d H:i:s')): ?>
                            <span class="badge badge-danger">Locked</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-secondary" href="/admin/users/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <?php if ((int) $row['id'] !== (int) current_user()['id']): ?>
                            <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                               data-action="/admin/users/<?= (int) $row['id'] ?>/delete"
                               data-confirm-message="Delete user &quot;<?= e($row['name']) ?>&quot;? This cannot be undone.">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

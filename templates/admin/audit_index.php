<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $entity @var string $action @var array $entities */ ?>
<div class="page-header">
    <h1>Audit log</h1>
</div>

<div class="table-toolbar">
    <form method="get" action="/admin/audit-log" style="display:flex;gap:10px;flex-wrap:wrap;">
        <select name="entity" onchange="this.form.submit()">
            <option value="">All record types</option>
            <?php foreach ($entities as $e): ?>
                <option value="<?= e($e) ?>" <?= $entity === $e ? 'selected' : '' ?>><?= e(ucfirst($e)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="action" onchange="this.form.submit()">
            <option value="">All actions</option>
            <?php foreach (['create', 'update', 'delete'] as $a): ?>
                <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e(ucfirst($a)) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No audit entries match these filters.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Summary</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['user_name'] ?? 'Deleted user') ?></td>
                    <td><span class="badge"><?= e(ucfirst($row['action'])) ?></span></td>
                    <td><?= e(ucfirst($row['entity'])) ?> #<?= (int) $row['entity_id'] ?></td>
                    <td><?= e($row['summary']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

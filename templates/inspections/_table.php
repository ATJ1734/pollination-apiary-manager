<?php /** @var array $rows @var int $total @var int $page @var int $perPage */ ?>
<?php if ($rows === []): ?>
    <div class="empty-state card">
        <div class="hex"></div>
        <p>No inspections match your search yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Date</th><th>Hive</th><th>Apiary</th><th>Inspector</th><th>Frames</th><th>Mites/100</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['inspected_on']) ?></td>
                    <td><a href="/hives/<?= (int) $row['hive_id'] ?>"><?= e($row['hive_code']) ?></a></td>
                    <td><?= e($row['apiary_name']) ?></td>
                    <td><?= e($row['inspector_name']) ?></td>
                    <td><?= (int) $row['frames_of_bees'] ?></td>
                    <td><?= $row['mites_per_300'] !== null ? round($row['mites_per_300'] / 3, 1) : '—' ?></td>
                    <td>
                        <?php if (!is_field_tech()): ?>
                            <a class="btn btn-sm btn-secondary" href="/inspections/<?= (int) $row['id'] ?>/edit">Edit</a>
                            <a class="btn btn-sm btn-danger" href="#" data-confirm-delete
                               data-action="/inspections/<?= (int) $row['id'] ?>/delete"
                               data-confirm-message="Delete this inspection? This cannot be undone.">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= \App\View\View::renderBare('partials/pagination', ['page' => $page, 'perPage' => $perPage, 'total' => $total]) ?>
<?php endif; ?>

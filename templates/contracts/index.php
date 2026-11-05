<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q @var string $status */ ?>
<div class="page-header">
    <h1>Contracts</h1>
    <?php if (!is_field_tech()): ?>
        <a class="btn btn-primary" href="/contracts/create">New contract</a>
    <?php endif; ?>
</div>

<div class="table-toolbar">
    <form method="get" action="/contracts" style="display:flex;gap:10px;flex-wrap:wrap;">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search crop or grower..."
               data-search-input data-search-url="/contracts/search" data-search-target="#contracts-table">
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (['draft', 'signed', 'active', 'completed', 'invoiced'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (!is_field_tech()): ?>
        <a class="btn btn-secondary" href="/export/contracts.csv">Export CSV</a>
    <?php endif; ?>
</div>

<div id="contracts-table">
    <?= \App\View\View::renderBare('contracts/_table', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage]) ?>
</div>

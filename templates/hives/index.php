<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q @var string $status @var mixed $apiary_id @var array $apiaries */ ?>
<div class="page-header">
    <h1>Hives</h1>
    <?php if (!is_field_tech()): ?>
        <a class="btn btn-primary" href="/hives/create">New hive</a>
    <?php endif; ?>
</div>

<div class="table-toolbar">
    <form method="get" action="/hives" id="hives-filter-form" style="display:flex;gap:10px;flex-wrap:wrap;">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search hive code or apiary..."
               data-search-input data-search-url="/hives/search" data-search-target="#hives-table">
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (['active', 'queenless', 'dead', 'sold', 'combined'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="apiary_id" onchange="this.form.submit()">
            <option value="">All apiaries</option>
            <?php foreach ($apiaries as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= (string) $apiary_id === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <a class="btn btn-secondary" href="/export/hives.csv">Export CSV</a>
</div>

<div id="hives-table">
    <?= \App\View\View::renderBare('hives/_table', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage]) ?>
</div>

<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q */ ?>
<div class="page-header">
    <h1>Inspections</h1>
    <a class="btn btn-primary" href="/inspections/create">Log inspection</a>
</div>

<div class="table-toolbar">
    <form method="get" action="/inspections">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by hive code or apiary..."
               data-search-input data-search-url="/inspections/search" data-search-target="#inspections-table">
    </form>
    <a class="btn btn-secondary" href="/export/inspections.csv">Export CSV</a>
</div>

<div id="inspections-table">
    <?= \App\View\View::renderBare('inspections/_table', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage]) ?>
</div>

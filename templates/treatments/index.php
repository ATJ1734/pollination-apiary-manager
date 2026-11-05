<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q */ ?>
<div class="page-header">
    <h1>Treatments</h1>
    <a class="btn btn-primary" href="/treatments/create">Log treatment</a>
</div>

<div class="table-toolbar">
    <form method="get" action="/treatments">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by hive code or product..."
               data-search-input data-search-url="/treatments/search" data-search-target="#treatments-table">
    </form>
    <a class="btn btn-secondary" href="/export/treatments.csv">Export CSV</a>
</div>

<div id="treatments-table">
    <?= \App\View\View::renderBare('treatments/_table', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage]) ?>
</div>

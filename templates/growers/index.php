<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q */ ?>
<div class="page-header">
    <h1>Growers</h1>
    <a class="btn btn-primary" href="/growers/create">New grower</a>
</div>

<div class="table-toolbar">
    <form method="get" action="/growers">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search growers..."
               data-search-input data-search-url="/growers/search" data-search-target="#growers-table">
    </form>
    <a class="btn btn-secondary" href="/export/growers.csv">Export CSV</a>
</div>

<div id="growers-table">
    <?= \App\View\View::renderBare('growers/_table', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage]) ?>
</div>

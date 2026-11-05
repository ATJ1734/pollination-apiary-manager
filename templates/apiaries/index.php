<?php /** @var array $rows @var int $total @var int $page @var int $perPage @var string $q */ ?>
<div class="page-header">
    <h1>Apiaries</h1>
    <?php if (!is_field_tech()): ?>
        <a class="btn btn-primary" href="/apiaries/create">New apiary</a>
    <?php endif; ?>
</div>

<div class="table-toolbar">
    <form method="get" action="/apiaries">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search apiaries..."
               data-search-input data-search-url="/apiaries/search" data-search-target="#apiaries-table">
    </form>
    <a class="btn btn-secondary" href="/export/apiaries.csv">Export CSV</a>
</div>

<div id="apiaries-table">
    <?= \App\View\View::renderBare('apiaries/_table', ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage]) ?>
</div>

<?php
/** @var int $page */
/** @var int $perPage */
/** @var int $total */

$lastPage = max(1, (int) ceil($total / $perPage));
if ($lastPage <= 1) {
    return;
}

$query = $_GET;
?>
<nav class="pagination" aria-label="Pagination">
    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
        <?php $query['page'] = $p; ?>
        <?php if ($p === $page): ?>
            <span class="current"><?= $p ?></span>
        <?php else: ?>
            <a href="?<?= e(http_build_query($query)) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</nav>

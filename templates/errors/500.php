<?php /** @var bool $debug */ /** @var string $message */ /** @var string $trace */ ?>
<div class="empty-state card">
    <div class="hex"></div>
    <h1>Something went wrong</h1>
    <p>An unexpected error occurred. Please try again, or contact support if it continues.</p>
    <?php if ($debug): ?>
        <pre style="text-align:left;background:var(--danger-tint);padding:12px;border-radius:8px;overflow:auto;"><?= e($message . "\n\n" . $trace) ?></pre>
    <?php endif; ?>
    <a class="btn btn-primary" href="/dashboard">Back to dashboard</a>
</div>

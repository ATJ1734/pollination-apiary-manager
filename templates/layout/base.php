<?php
/** @var string $content */
/** @var array $company */
/** @var string $appName */
/** @var array|null $user */
/** @var list<array{type:string,message:string}> $flashes */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$navActive = static fn (string $prefix): string => str_starts_with($path, $prefix) ? ' class="active"' : '';
$isFieldTech = is_field_tech();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appName) ?></title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/print.css" media="print">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<?php if ($user): ?>
<header class="app-header">
    <a class="brand" href="/dashboard">
        <img src="<?= e($company['logo']) ?>" alt="" width="32" height="28">
        <span>
            <?= e($company['name']) ?>
            <?php if ($company['tagline'] && !str_starts_with($company['tagline'], '[')): ?>
                <span class="tagline"><?= e($company['tagline']) ?></span>
            <?php endif; ?>
        </span>
    </a>
</header>
<nav class="app-nav">
    <a href="/dashboard"<?= $navActive('/dashboard') ?>>Dashboard</a>
    <a href="/apiaries"<?= $navActive('/apiaries') ?>>Apiaries</a>
    <a href="/hives"<?= $navActive('/hives') ?>>Hives</a>
    <a href="/inspections"<?= $navActive('/inspections') ?>>Inspections</a>
    <a href="/treatments"<?= $navActive('/treatments') ?>>Treatments</a>
    <?php if (!$isFieldTech): ?>
        <a href="/growers"<?= $navActive('/growers') ?>>Growers</a>
    <?php endif; ?>
    <a href="/contracts"<?= $navActive('/contracts') ?>>Contracts</a>
    <?php if ($user['role'] === 'admin'): ?>
        <a href="/admin/users"<?= $navActive('/admin') ?>>Admin</a>
    <?php endif; ?>
    <span class="spacer"></span>
    <span style="padding:10px 14px;color:var(--brand-tint);"><?= e($user['name']) ?> &middot; <?= e($user['role']) ?></span>
    <form method="post" action="/logout">
        <?= csrf_field() ?>
        <button type="submit" class="link-button">Sign out</button>
    </form>
</nav>
<?php endif; ?>

<main id="main" class="app-main">
    <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<?php if ($user): ?>
<footer class="app-footer">
    <div class="footer-inner">
        <strong><?= e($company['name']) ?></strong><br>
        <?= e($company['address']) ?><br>
        <?= e($company['phone']) ?> &middot; <?= e($company['email']) ?> &middot; <?= e($company['website']) ?>
    </div>
</footer>
<?php endif; ?>

<div class="modal-backdrop" id="confirm-modal">
    <div class="modal-box">
        <p id="confirm-modal-message">Are you sure?</p>
        <form method="post" id="confirm-modal-form">
            <?= csrf_field() ?>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" data-modal-cancel>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<script type="module" src="/assets/js/app.js"></script>
</body>
</html>

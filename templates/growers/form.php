<?php /** @var array|null $item @var array $errors */
$v = static fn (string $field) => e((string) ($item[$field] ?? old($field)));
?>
<div class="page-header">
    <h1><?= $item ? 'Edit grower' : 'New grower' ?></h1>
</div>

<form method="post" action="<?= $item ? "/growers/{$item['id']}" : '/growers' ?>" class="card">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field <?= isset($errors['farm_name']) ? 'has-error' : '' ?>">
            <label for="farm_name">Farm name</label>
            <input type="text" id="farm_name" name="farm_name" value="<?= $v('farm_name') ?>" required>
            <?php if (isset($errors['farm_name'])): ?><p class="error-text"><?= e($errors['farm_name']) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="contact_name">Contact name</label>
            <input type="text" id="contact_name" name="contact_name" value="<?= $v('contact_name') ?>">
        </div>
        <div class="field">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= $v('phone') ?>">
        </div>
        <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= $v('email') ?>">
            <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= $v('address') ?>">
        </div>
    </div>
    <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"><?= $v('notes') ?></textarea>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $item ? 'Save changes' : 'Create grower' ?></button>
        <a class="btn btn-secondary" href="/growers">Cancel</a>
    </div>
</form>

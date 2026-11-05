<?php
/** @var array|null $item @var array $errors @var array $hives @var int $preselectedHiveId @var string $today @var bool $editing */
$editing = $editing ?? false;
$v = static fn (string $field, mixed $default = '') => e((string) ($item[$field] ?? old($field, (string) $default)));
?>
<div class="page-header">
    <h1><?= $editing ? 'Edit treatment' : 'Log a treatment' ?></h1>
</div>

<form method="post" action="<?= $editing ? "/treatments/{$item['id']}" : '/treatments' ?>" class="card" style="max-width:480px;">
    <?= csrf_field() ?>

    <div class="field <?= isset($errors['hive_id']) ? 'has-error' : '' ?>">
        <label for="hive_id">Hive</label>
        <select id="hive_id" name="hive_id" required>
            <option value="">Choose a hive</option>
            <?php foreach ($hives as $h): ?>
                <option value="<?= (int) $h['id'] ?>" <?= $preselectedHiveId === (int) $h['id'] ? 'selected' : '' ?>><?= e($h['hive_code']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['hive_id'])): ?><p class="error-text"><?= e($errors['hive_id']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['product']) ? 'has-error' : '' ?>">
        <label for="product">Product</label>
        <input type="text" id="product" name="product" value="<?= $v('product') ?>" placeholder="Formic Pro" required>
        <?php if (isset($errors['product'])): ?><p class="error-text"><?= e($errors['product']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label for="dose">Dose</label>
        <input type="text" id="dose" name="dose" value="<?= $v('dose') ?>" placeholder="2 strips">
    </div>

    <div class="field">
        <label for="applied_on">Applied on</label>
        <input type="date" id="applied_on" name="applied_on" value="<?= $v('applied_on', $today) ?>" required>
    </div>

    <div class="field <?= isset($errors['remove_by']) ? 'has-error' : '' ?>">
        <label for="remove_by">Remove by</label>
        <input type="date" id="remove_by" name="remove_by" value="<?= $v('remove_by') ?>">
        <?php if (isset($errors['remove_by'])): ?><p class="error-text"><?= e($errors['remove_by']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['removed_on']) ? 'has-error' : '' ?>">
        <label for="removed_on">Removed on</label>
        <input type="date" id="removed_on" name="removed_on" value="<?= $v('removed_on') ?>">
        <p class="hint">Leave blank until the strips/treatment is actually pulled.</p>
        <?php if (isset($errors['removed_on'])): ?><p class="error-text"><?= e($errors['removed_on']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"><?= $v('notes') ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save changes' : 'Save treatment' ?></button>
        <a class="btn btn-secondary" href="/treatments">Cancel</a>
    </div>
</form>

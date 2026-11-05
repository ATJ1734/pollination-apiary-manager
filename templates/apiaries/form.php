<?php /** @var array|null $item @var array $errors */
$v = static fn (string $field, string $default = '') => e((string) ($item[$field] ?? old($field, $default)));
?>
<div class="page-header">
    <h1><?= $item ? 'Edit apiary' : 'New apiary' ?></h1>
</div>

<form method="post" action="<?= $item ? "/apiaries/{$item['id']}" : '/apiaries' ?>" class="card">
    <?= csrf_field() ?>

    <div class="form-grid">
        <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= $v('name') ?>" required>
            <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="location_description">Location description</label>
            <input type="text" id="location_description" name="location_description" value="<?= $v('location_description') ?>">
        </div>

        <div class="field <?= isset($errors['latitude']) ? 'has-error' : '' ?>">
            <label for="latitude">Latitude</label>
            <input type="text" id="latitude" name="latitude" value="<?= $v('latitude') ?>">
            <?php if (isset($errors['latitude'])): ?><p class="error-text"><?= e($errors['latitude']) ?></p><?php endif; ?>
        </div>

        <div class="field <?= isset($errors['longitude']) ? 'has-error' : '' ?>">
            <label for="longitude">Longitude</label>
            <input type="text" id="longitude" name="longitude" value="<?= $v('longitude') ?>">
            <?php if (isset($errors['longitude'])): ?><p class="error-text"><?= e($errors['longitude']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="landowner_name">Landowner name</label>
            <input type="text" id="landowner_name" name="landowner_name" value="<?= $v('landowner_name') ?>">
        </div>

        <div class="field">
            <label for="landowner_phone">Landowner phone</label>
            <input type="text" id="landowner_phone" name="landowner_phone" value="<?= $v('landowner_phone') ?>">
        </div>
    </div>

    <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"><?= $v('notes') ?></textarea>
    </div>

    <div class="field checkbox-field">
        <input type="checkbox" id="active" name="active" value="1" <?= ($item['active'] ?? 1) ? 'checked' : '' ?>>
        <label for="active" style="margin:0;">Active</label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $item ? 'Save changes' : 'Create apiary' ?></button>
        <a class="btn btn-secondary" href="/apiaries">Cancel</a>
    </div>
</form>

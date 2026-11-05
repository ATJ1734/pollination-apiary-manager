<?php /** @var array|null $item @var array $errors @var array $roles */
$v = static fn (string $field, mixed $default = '') => e((string) ($item[$field] ?? old($field, (string) $default)));
?>
<div class="page-header">
    <h1><?= $item ? 'Edit user' : 'New user' ?></h1>
</div>

<form method="post" action="<?= $item ? "/admin/users/{$item['id']}" : '/admin/users' ?>" class="card" style="max-width:480px;">
    <?= csrf_field() ?>

    <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= $v('name') ?>" required>
        <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= $v('email') ?>" required>
        <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role">
            <?php foreach ($roles as $r): ?>
                <option value="<?= e($r) ?>" <?= ($item['role'] ?? 'field_tech') === $r ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $r))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field checkbox-field">
        <input type="checkbox" id="active" name="active" value="1" <?= ($item['active'] ?? 1) ? 'checked' : '' ?>>
        <label for="active" style="margin:0;">Active</label>
    </div>

    <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
        <label for="password">Password<?= $item ? ' (leave blank to keep current)' : '' ?></label>
        <input type="password" id="password" name="password" autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $item ? 'Save changes' : 'Create user' ?></button>
        <a class="btn btn-secondary" href="/admin/users">Cancel</a>
    </div>
</form>

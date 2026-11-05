<?php /** @var array|null $item @var array $errors @var array $apiaries @var array $statuses */
$v = static fn (string $field) => e((string) ($item[$field] ?? old($field)));
?>
<div class="page-header">
    <h1><?= $item ? 'Edit hive' : 'New hive' ?></h1>
</div>

<form method="post" action="<?= $item ? "/hives/{$item['id']}" : '/hives' ?>" class="card">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field <?= isset($errors['hive_code']) ? 'has-error' : '' ?>">
            <label for="hive_code">Hive code</label>
            <input type="text" id="hive_code" name="hive_code" value="<?= $v('hive_code') ?>" placeholder="H-0001" required>
            <?php if (isset($errors['hive_code'])): ?><p class="error-text"><?= e($errors['hive_code']) ?></p><?php endif; ?>
        </div>

        <div class="field <?= isset($errors['apiary_id']) ? 'has-error' : '' ?>">
            <label for="apiary_id">Apiary</label>
            <select id="apiary_id" name="apiary_id" required>
                <option value="">Choose an apiary</option>
                <?php foreach ($apiaries as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (string) ($item['apiary_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['apiary_id'])): ?><p class="error-text"><?= e($errors['apiary_id']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($item['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="hive_type">Hive type</label>
            <input type="text" id="hive_type" name="hive_type" value="<?= $v('hive_type') ?>" placeholder="Langstroth deep">
        </div>

        <div class="field">
            <label for="established_on">Established on</label>
            <input type="date" id="established_on" name="established_on" value="<?= $v('established_on') ?>">
        </div>

        <div class="field">
            <label for="queen_installed_on">Queen installed on</label>
            <input type="date" id="queen_installed_on" name="queen_installed_on" value="<?= $v('queen_installed_on') ?>">
        </div>

        <div class="field">
            <label for="queen_source">Queen source</label>
            <input type="text" id="queen_source" name="queen_source" value="<?= $v('queen_source') ?>">
        </div>

        <div class="field">
            <label for="queen_mark_color">Queen mark color</label>
            <input type="text" id="queen_mark_color" name="queen_mark_color" value="<?= $v('queen_mark_color') ?>">
        </div>
    </div>

    <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"><?= $v('notes') ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $item ? 'Save changes' : 'Create hive' ?></button>
        <a class="btn btn-secondary" href="/hives">Cancel</a>
    </div>
</form>

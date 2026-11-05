<?php /** @var array|null $item @var array $errors @var array $growers @var array $statuses */
$v = static fn (string $field, mixed $default = '') => e((string) ($item[$field] ?? old($field, (string) $default)));
?>
<div class="page-header">
    <h1><?= $item ? 'Edit contract' : 'New contract' ?></h1>
</div>

<form method="post" action="<?= $item ? "/contracts/{$item['id']}" : '/contracts' ?>" class="card">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field <?= isset($errors['grower_id']) ? 'has-error' : '' ?>">
            <label for="grower_id">Grower</label>
            <select id="grower_id" name="grower_id" required>
                <option value="">Choose a grower</option>
                <?php foreach ($growers as $g): ?>
                    <option value="<?= (int) $g['id'] ?>" <?= (string) ($item['grower_id'] ?? '') === (string) $g['id'] ? 'selected' : '' ?>><?= e($g['farm_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['grower_id'])): ?><p class="error-text"><?= e($errors['grower_id']) ?></p><?php endif; ?>
        </div>

        <div class="field <?= isset($errors['crop']) ? 'has-error' : '' ?>">
            <label for="crop">Crop</label>
            <input type="text" id="crop" name="crop" value="<?= $v('crop') ?>" placeholder="almonds" required>
            <?php if (isset($errors['crop'])): ?><p class="error-text"><?= e($errors['crop']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="season_year">Season year</label>
            <input type="number" id="season_year" name="season_year" value="<?= $v('season_year', (string) date('Y')) ?>" required>
        </div>

        <div class="field <?= isset($errors['hives_required']) ? 'has-error' : '' ?>">
            <label for="hives_required">Hives required</label>
            <input type="number" id="hives_required" name="hives_required" value="<?= $v('hives_required') ?>" min="1" required>
            <?php if (isset($errors['hives_required'])): ?><p class="error-text"><?= e($errors['hives_required']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="min_frames_of_bees">Minimum frames of bees</label>
            <input type="number" id="min_frames_of_bees" name="min_frames_of_bees" value="<?= $v('min_frames_of_bees') ?>" min="0" required>
        </div>

        <div class="field">
            <label for="rate_per_hive">Rate per hive ($)</label>
            <input type="number" step="0.01" id="rate_per_hive" name="rate_per_hive" value="<?= $v('rate_per_hive') ?>" min="0" required>
        </div>

        <div class="field">
            <label for="start_date">Start date</label>
            <input type="date" id="start_date" name="start_date" value="<?= $v('start_date') ?>" required>
        </div>

        <div class="field <?= isset($errors['end_date']) ? 'has-error' : '' ?>">
            <label for="end_date">End date</label>
            <input type="date" id="end_date" name="end_date" value="<?= $v('end_date') ?>" required>
            <?php if (isset($errors['end_date'])): ?><p class="error-text"><?= e($errors['end_date']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($item['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $item ? 'Save changes' : 'Create contract' ?></button>
        <a class="btn btn-secondary" href="/contracts">Cancel</a>
    </div>
</form>

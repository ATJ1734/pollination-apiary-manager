<?php
/** @var array|null $item @var array $errors @var array $hives @var int $preselectedHiveId @var string $today @var bool $editing */
$editing = $editing ?? false;
$v = static fn (string $field, mixed $default = '') => e((string) ($item[$field] ?? old($field, (string) $default)));
$stepper = static function (string $field, int $value, int $min, int $max, string $label) use ($errors) {
    ?>
    <div class="field <?= isset($errors[$field]) ? 'has-error' : '' ?>">
        <label for="<?= $field ?>"><?= e($label) ?></label>
        <div class="stepper">
            <button type="button" data-step="-1" data-step-target="<?= $field ?>" aria-label="Decrease <?= e($label) ?>">&minus;</button>
            <input type="number" id="<?= $field ?>" name="<?= $field ?>" value="<?= $value ?>" min="<?= $min ?>" max="<?= $max ?>" inputmode="numeric">
            <button type="button" data-step="1" data-step-target="<?= $field ?>" aria-label="Increase <?= e($label) ?>">+</button>
        </div>
        <?php if (isset($errors[$field])): ?><p class="error-text"><?= e($errors[$field]) ?></p><?php endif; ?>
    </div>
    <?php
};
$toggle = static function (string $field, bool $value, string $label) {
    ?>
    <div class="field toggle-field">
        <label for="<?= $field ?>_toggle" style="margin:0;"><?= e($label) ?></label>
        <input type="hidden" id="<?= $field ?>" name="<?= $field ?>" value="<?= $value ? '1' : '0' ?>">
        <button type="button" id="<?= $field ?>_toggle" data-toggle data-toggle-target="<?= $field ?>">
            <?= $value ? 'Yes' : 'No' ?>
        </button>
    </div>
    <?php
};
?>
<div class="page-header">
    <h1><?= $editing ? 'Edit inspection' : 'Log an inspection' ?></h1>
</div>

<form method="post" action="<?= $editing ? "/inspections/{$item['id']}" : '/inspections' ?>" class="card" style="max-width:480px;">
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

    <div class="field">
        <label for="inspected_on">Date</label>
        <input type="date" id="inspected_on" name="inspected_on" value="<?= $v('inspected_on', $today) ?>" required>
    </div>

    <?php if (!$editing): ?>
        <p class="hint">Inspector: <?= e(current_user()['name']) ?> (you)</p>
    <?php endif; ?>

    <?= $stepper('frames_of_bees', (int) ($item['frames_of_bees'] ?? 8), 0, 40, 'Frames of bees') ?>
    <?= $stepper('brood_frames', (int) ($item['brood_frames'] ?? 5), 0, 40, 'Brood frames') ?>

    <?= $toggle('queen_seen', (bool) ($item['queen_seen'] ?? false), 'Queen seen') ?>
    <?= $toggle('eggs_seen', (bool) ($item['eggs_seen'] ?? false), 'Eggs seen') ?>

    <?= $stepper('temperament', (int) ($item['temperament'] ?? 3), 1, 5, 'Temperament (1 calm – 5 defensive)') ?>
    <?= $stepper('honey_stores', (int) ($item['honey_stores'] ?? 3), 1, 5, 'Honey stores (1 low – 5 full)') ?>

    <div class="field <?= isset($errors['mites_per_300']) ? 'has-error' : '' ?>">
        <label for="mites_per_300">Mite wash count (per 300 bees)</label>
        <input type="number" id="mites_per_300" name="mites_per_300" min="0" max="300"
               value="<?= e((string) ($item['mites_per_300'] ?? '')) ?>" placeholder="Leave blank if not measured">
        <p class="hint">Alcohol wash count. Divide by 3 for mites per 100 bees.</p>
        <?php if (isset($errors['mites_per_300'])): ?><p class="error-text"><?= e($errors['mites_per_300']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label for="disease_notes">Disease notes</label>
        <textarea id="disease_notes" name="disease_notes"><?= $v('disease_notes') ?></textarea>
    </div>

    <div class="field">
        <label for="actions_taken">Actions taken</label>
        <textarea id="actions_taken" name="actions_taken"><?= $v('actions_taken') ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save changes' : 'Save inspection' ?></button>
        <a class="btn btn-secondary" href="/inspections">Cancel</a>
    </div>
</form>

<div class="login-page">
    <div class="login-card">
        <div class="brand">
            <img src="<?= e($company['logo']) ?>" alt="" width="48" height="42">
            <span class="company-name"><?= e($company['name']) ?></span>
        </div>
        <form method="post" action="/login">
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" style="width:100%;">Sign in</button>
            </div>
        </form>
    </div>
</div>

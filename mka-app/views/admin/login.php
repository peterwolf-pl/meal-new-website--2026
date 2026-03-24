<section class="admin-auth">
    <div class="admin-auth-card">
        <p class="section-kicker">MKA CMS</p>
        <h1>Logowanie</h1>
        <?php if (!empty($errors['credentials'])): ?>
            <p class="form-error"><?= e($errors['credentials']) ?></p>
        <?php endif; ?>
        <form method="post" action="/admin/login" class="admin-form-grid">
            <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
            <label>
                <span>E-mail</span>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                <?php if (!empty($errors['email'])): ?><small class="form-error"><?= e($errors['email']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Hasło</span>
                <input type="password" name="password" required>
                <?php if (!empty($errors['password'])): ?><small class="form-error"><?= e($errors['password']) ?></small><?php endif; ?>
            </label>
            <button class="button-primary" type="submit">Zaloguj</button>
        </form>
        <p class="helper-note">Domyślny użytkownik po seedzie: <code>admin@mka.local</code></p>
    </div>
</section>

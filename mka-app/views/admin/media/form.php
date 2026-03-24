<section class="admin-page-head">
    <div>
        <p class="section-kicker">Media</p>
        <h1><?= e($pageTitle) ?></h1>
    </div>
    <div class="admin-actions">
        <a class="button-secondary" href="/admin/media">Powrót do listy</a>
    </div>
</section>

<?php if (!empty($errors)): ?>
    <div class="form-error-box">
        <p>Popraw pola formularza zaznaczone poniżej.</p>
    </div>
<?php endif; ?>

<form method="post" action="/admin/media/save" enctype="multipart/form-data" class="admin-form-stack">
    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
    <input type="hidden" name="id" value="<?= e((string) ($form['id'] ?? '')) ?>">

    <section class="admin-form-card">
        <h2>Plik i typ</h2>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Typ medium</span>
                <select name="kind">
                    <option value="image" <?= selected('image', $form['kind']) ?>>Obraz</option>
                    <option value="pdf" <?= selected('pdf', $form['kind']) ?>>PDF</option>
                    <option value="font" <?= selected('font', $form['kind']) ?>>Font</option>
                    <option value="video" <?= selected('video', $form['kind']) ?>>Wideo zewnętrzne</option>
                </select>
                <?php if (!empty($errors['kind'])): ?><small class="form-error"><?= e($errors['kind']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Upload pliku</span>
                <input type="file" name="upload">
                <?php if (!empty($errors['upload'])): ?><small class="form-error"><?= e($errors['upload']) ?></small><?php endif; ?>
            </label>
            <label class="span-2">
                <span>URL zewnętrzny</span>
                <input type="url" name="external_url" value="<?= e((string) $form['external_url']) ?>" placeholder="Dla wideo lub mediów demo">
                <?php if (!empty($errors['external_url'])): ?><small class="form-error"><?= e($errors['external_url']) ?></small><?php endif; ?>
            </label>
        </div>
        <?php if (!empty($form['public_url'])): ?>
            <p class="helper-note">Aktualny adres: <a href="<?= e((string) $form['public_url']) ?>" target="_blank" rel="noreferrer"><?= e((string) $form['public_url']) ?></a></p>
        <?php endif; ?>
        <p class="helper-note">Dla fontów użyj lokalnego uploadu w formacie <code>.woff2</code>, <code>.woff</code>, <code>.ttf</code> albo <code>.otf</code>.</p>
        <label class="checkbox-field">
            <input type="checkbox" name="is_decorative" value="1" <?= checked(!empty($form['is_decorative'])) ?>>
            <span>Medium dekoracyjne</span>
        </label>
    </section>

    <section class="admin-form-card">
        <h2>Metadane PL</h2>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Tytuł</span>
                <input type="text" name="translations[pl][title]" value="<?= e($form['translations']['pl']['title']) ?>" required>
                <?php if (!empty($errors['translations.pl.title'])): ?><small class="form-error"><?= e($errors['translations.pl.title']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Alt</span>
                <input type="text" name="translations[pl][alt_text]" value="<?= e($form['translations']['pl']['alt_text']) ?>">
            </label>
            <label class="span-2">
                <span>Podpis</span>
                <textarea name="translations[pl][caption]" rows="3"><?= e($form['translations']['pl']['caption']) ?></textarea>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <h2>Metadane EN</h2>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Tytuł</span>
                <input type="text" name="translations[en][title]" value="<?= e($form['translations']['en']['title']) ?>">
            </label>
            <label>
                <span>Alt</span>
                <input type="text" name="translations[en][alt_text]" value="<?= e($form['translations']['en']['alt_text']) ?>">
            </label>
            <label class="span-2">
                <span>Podpis</span>
                <textarea name="translations[en][caption]" rows="3"><?= e($form['translations']['en']['caption']) ?></textarea>
            </label>
        </div>
    </section>

    <div class="admin-submit-row">
        <button class="button-primary" type="submit">Zapisz medium</button>
        <?php if (!$isNew): ?>
            <button
                class="button-danger"
                type="submit"
                formaction="/admin/media/<?= e((string) $form['id']) ?>/delete"
                formmethod="post"
                onclick="return confirm('Usunąć to medium?');"
            >
                Usuń
            </button>
        <?php endif; ?>
    </div>
</form>

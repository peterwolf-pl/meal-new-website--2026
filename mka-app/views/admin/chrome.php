<section class="admin-page-head">
    <div>
        <p class="section-kicker">Header i footer</p>
        <h1>Układ globalny</h1>
    </div>
</section>

<?php if (!empty($errors)): ?>
    <div class="form-error-box">
        <p>Popraw pola formularza zaznaczone poniżej.</p>
    </div>
<?php endif; ?>

<form method="post" action="/admin/chrome/save" class="admin-form-stack">
    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">

    <section class="admin-form-card">
        <h2>Header</h2>
        <div class="admin-form-grid columns-2">
            <label class="span-2">
                <span>Logotyp w headerze</span>
                <select name="header_logo_media_id">
                    <option value="">- użyj tekstu po lewej -</option>
                    <?php foreach ($mediaAssets as $asset): ?>
                        <option value="<?= e((string) $asset['id']) ?>" <?= ((int) ($form['header_logo_media_id'] ?? 0) === (int) $asset['id']) ? 'selected' : '' ?>>
                            #<?= e((string) $asset['id']) ?> <?= e($asset['title']) ?> (<?= e($asset['kind']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Kolor tła headera</span>
                <input type="text" name="header_background_color" value="<?= e((string) ($form['header_background_color'] ?? '#ffffff')) ?>" placeholder="#ffffff">
                <?php if (!empty($errors['header_background_color'])): ?><small class="form-error"><?= e($errors['header_background_color']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kolor fontów headera</span>
                <input type="text" name="header_font_color" value="<?= e((string) ($form['header_font_color'] ?? '#181614')) ?>" placeholder="#181614">
                <?php if (!empty($errors['header_font_color'])): ?><small class="form-error"><?= e($errors['header_font_color']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Wysokość headera</span>
                <input type="text" name="header_height" value="<?= e((string) ($form['header_height'] ?? '50px')) ?>" placeholder="np. 50px">
                <?php if (!empty($errors['header_height'])): ?><small class="form-error"><?= e($errors['header_height']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Padding logotypu</span>
                <input type="text" name="header_logo_padding" value="<?= e((string) ($form['header_logo_padding'] ?? '0')) ?>" placeholder="np. 0 12px">
                <?php if (!empty($errors['header_logo_padding'])): ?><small class="form-error"><?= e($errors['header_logo_padding']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Margin logotypu</span>
                <input type="text" name="header_logo_margin" value="<?= e((string) ($form['header_logo_margin'] ?? '0')) ?>" placeholder="np. 0">
                <?php if (!empty($errors['header_logo_margin'])): ?><small class="form-error"><?= e($errors['header_logo_margin']) ?></small><?php endif; ?>
            </label>
            <label class="span-2">
                <span>Linki po prawej stronie headera</span>
                <textarea name="header_links_html" rows="4"><?= e((string) ($form['header_links_html'] ?? '')) ?></textarea>
                <small class="helper-note">Dozwolone są bezpieczne znaczniki HTML, w tym <code>&lt;a&gt;</code> i <code>&lt;i class="fa-solid fa-..."&gt;</code>, np. <code>&lt;a href="/pl/kontakt"&gt;&lt;i class="fa-solid fa-envelope"&gt;&lt;/i&gt; Kontakt&lt;/a&gt;</code>.</small>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="header_show_cms_link" value="1" <?= checked(!empty($form['header_show_cms_link'])) ?>>
                <span>Pokaż link do CMS w headerze</span>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <h2>Footer</h2>
        <div class="admin-form-grid columns-2">
            <label class="checkbox-field span-2">
                <input type="checkbox" name="footer_enabled" value="1" <?= checked(!empty($form['footer_enabled'])) ?>>
                <span>Włącz cały footer</span>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="footer_bar_enabled" value="1" <?= checked(!empty($form['footer_bar_enabled'])) ?>>
                <span>Włącz dolny pasek copyright</span>
            </label>
            <label class="span-2">
                <span>Treść dolnego paska</span>
                <textarea name="footer_bar_text" rows="3"><?= e((string) ($form['footer_bar_text'] ?? '')) ?></textarea>
                <small class="helper-note">Dozwolone są bezpieczne znaczniki HTML, w tym <code>&lt;a href=""&gt;</code> oraz <code>&lt;br&gt;</code>.</small>
                <?php if (!empty($errors['footer_bar_text'])): ?><small class="form-error"><?= e($errors['footer_bar_text']) ?></small><?php endif; ?>
            </label>
        </div>
    </section>

    <div class="admin-submit-row">
        <button class="button-primary" type="submit">Zapisz header i footer</button>
    </div>
</form>

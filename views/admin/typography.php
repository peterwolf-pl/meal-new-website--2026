<section class="admin-page-head">
    <div>
        <p class="section-kicker">Typografia</p>
        <h1>Typografia publiczna</h1>
    </div>
</section>

<?php
$fontPresets = $fontPresets ?? [
    'editorial-sans' => ['label' => 'Editorial Sans', 'stack' => '"Avenir Next", "Gill Sans", "Trebuchet MS", sans-serif'],
    'library-serif' => ['label' => 'Library Serif', 'stack' => '"Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif'],
    'technical-sans' => ['label' => 'Technical Sans', 'stack' => '"Helvetica Neue", Helvetica, Arial, sans-serif'],
    'reading-serif' => ['label' => 'Reading Serif', 'stack' => 'Georgia, "Times New Roman", serif'],
    'monospace-editorial' => ['label' => 'Monospace Editorial', 'stack' => '"IBM Plex Mono", "Courier New", Courier, monospace'],
];
?>

<?php if (!empty($errors)): ?>
    <div class="form-error-box">
        <p>Popraw pola formularza zaznaczone poniżej.</p>
    </div>
<?php endif; ?>

<?php if (empty($supportsThemeFonts)): ?>
    <div class="form-error-box">
        <p>Zmiany typografii nie zapisują się jeszcze w bazie. Użyj zakładki <a href="/admin/database">Baza</a>, aby wykonać brakujące migracje.</p>
    </div>
<?php endif; ?>

<form method="post" action="/admin/typography/save" class="admin-form-stack">
    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">

    <section class="admin-form-card">
        <div class="panel-card-head">
            <div>
                <h2>Fonty i parametry</h2>
                <p class="helper-note">Presety działają jako fallback. Jeśli wybierzesz uploadowany font, zostanie użyty przed presetem.</p>
            </div>
            <a class="button-secondary" href="/admin/media/new">Dodaj font do biblioteki</a>
        </div>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Preset fontu treści</span>
                <select name="body_font_preset">
                    <?php foreach ($fontPresets as $key => $preset): ?>
                        <option value="<?= e((string) $key) ?>" <?= selected((string) $key, $form['body_font_preset']) ?>>
                            <?= e($preset['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['body_font_preset'])): ?><small class="form-error"><?= e($errors['body_font_preset']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Uploadowany font treści</span>
                <select name="body_font_media_id">
                    <option value="">- użyj tylko presetu -</option>
                    <?php foreach ($fontAssets as $asset): ?>
                        <option value="<?= e((string) $asset['id']) ?>" <?= ((int) ($form['body_font_media_id'] ?? 0) === (int) $asset['id']) ? 'selected' : '' ?>>
                            #<?= e((string) $asset['id']) ?> <?= e($asset['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['body_font_media_id'])): ?><small class="form-error"><?= e($errors['body_font_media_id']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Rozmiar fontu treści</span>
                <input type="text" name="body_font_size" value="<?= e((string) ($form['body_font_size'] ?? '1rem')) ?>" placeholder="np. 1rem">
                <?php if (!empty($errors['body_font_size'])): ?><small class="form-error"><?= e($errors['body_font_size']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Letter spacing treści</span>
                <input type="text" name="body_font_letter_spacing" value="<?= e((string) ($form['body_font_letter_spacing'] ?? '0')) ?>" placeholder="np. 0 lub 0.02em">
                <?php if (!empty($errors['body_font_letter_spacing'])): ?><small class="form-error"><?= e($errors['body_font_letter_spacing']) ?></small><?php endif; ?>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="body_font_uppercase" value="1" <?= checked(!empty($form['body_font_uppercase'])) ?>>
                <span>Make UPPERCASE treść</span>
            </label>

            <label>
                <span>Preset fontu nagłówków</span>
                <select name="heading_font_preset">
                    <?php foreach ($fontPresets as $key => $preset): ?>
                        <option value="<?= e((string) $key) ?>" <?= selected((string) $key, $form['heading_font_preset']) ?>>
                            <?= e($preset['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['heading_font_preset'])): ?><small class="form-error"><?= e($errors['heading_font_preset']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Uploadowany font nagłówków</span>
                <select name="heading_font_media_id">
                    <option value="">- użyj tylko presetu -</option>
                    <?php foreach ($fontAssets as $asset): ?>
                        <option value="<?= e((string) $asset['id']) ?>" <?= ((int) ($form['heading_font_media_id'] ?? 0) === (int) $asset['id']) ? 'selected' : '' ?>>
                            #<?= e((string) $asset['id']) ?> <?= e($asset['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['heading_font_media_id'])): ?><small class="form-error"><?= e($errors['heading_font_media_id']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Rozmiar fontu nagłówków</span>
                <input type="text" name="heading_font_size" value="<?= e((string) ($form['heading_font_size'] ?? 'clamp(1.8rem, 3vw, 2.8rem)')) ?>" placeholder="np. clamp(1.8rem, 3vw, 2.8rem)">
                <?php if (!empty($errors['heading_font_size'])): ?><small class="form-error"><?= e($errors['heading_font_size']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Letter spacing nagłówków</span>
                <input type="text" name="heading_font_letter_spacing" value="<?= e((string) ($form['heading_font_letter_spacing'] ?? '-0.04em')) ?>" placeholder="np. -0.04em">
                <?php if (!empty($errors['heading_font_letter_spacing'])): ?><small class="form-error"><?= e($errors['heading_font_letter_spacing']) ?></small><?php endif; ?>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="heading_font_capitalize" value="1" <?= checked(!empty($form['heading_font_capitalize'])) ?>>
                <span>Capitalize nagłówki</span>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="heading_font_uppercase" value="1" <?= checked(!empty($form['heading_font_uppercase'])) ?>>
                <span>Make UPPERCASE nagłówki</span>
            </label>

            <label>
                <span>Preset fontu menu</span>
                <select name="menu_font_preset">
                    <?php foreach ($fontPresets as $key => $preset): ?>
                        <option value="<?= e((string) $key) ?>" <?= selected((string) $key, $form['menu_font_preset']) ?>>
                            <?= e($preset['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['menu_font_preset'])): ?><small class="form-error"><?= e($errors['menu_font_preset']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Uploadowany font menu</span>
                <select name="menu_font_media_id">
                    <option value="">- użyj tylko presetu -</option>
                    <?php foreach ($fontAssets as $asset): ?>
                        <option value="<?= e((string) $asset['id']) ?>" <?= ((int) ($form['menu_font_media_id'] ?? 0) === (int) $asset['id']) ? 'selected' : '' ?>>
                            #<?= e((string) $asset['id']) ?> <?= e($asset['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['menu_font_media_id'])): ?><small class="form-error"><?= e($errors['menu_font_media_id']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Rozmiar fontu menu głównego</span>
                <input type="text" name="menu_font_size" value="<?= e((string) ($form['menu_font_size'] ?? 'clamp(2rem, 4vw, 3.6rem)')) ?>" placeholder="np. clamp(2rem, 4vw, 3.6rem)">
                <?php if (!empty($errors['menu_font_size'])): ?><small class="form-error"><?= e($errors['menu_font_size']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Letter spacing menu głównego</span>
                <input type="text" name="menu_font_letter_spacing" value="<?= e((string) ($form['menu_font_letter_spacing'] ?? '-0.04em')) ?>" placeholder="np. -0.04em">
                <?php if (!empty($errors['menu_font_letter_spacing'])): ?><small class="form-error"><?= e($errors['menu_font_letter_spacing']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kolor tła menu głównego</span>
                <input type="text" name="menu_background_color" value="<?= e((string) ($form['menu_background_color'] ?? '#ffffff')) ?>" placeholder="#ffffff">
                <?php if (!empty($errors['menu_background_color'])): ?><small class="form-error"><?= e($errors['menu_background_color']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kolor tła pozycji z rozwiniętym submenu</span>
                <input type="text" name="menu_submenu_background_color" value="<?= e((string) ($form['menu_submenu_background_color'] ?? '#ffffff')) ?>" placeholder="#ffffff">
                <?php if (!empty($errors['menu_submenu_background_color'])): ?><small class="form-error"><?= e($errors['menu_submenu_background_color']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kolor tła otwartej pozycji menu</span>
                <input type="text" name="menu_active_background_color" value="<?= e((string) ($form['menu_active_background_color'] ?? '#ffffff')) ?>" placeholder="#ffffff">
                <?php if (!empty($errors['menu_active_background_color'])): ?><small class="form-error"><?= e($errors['menu_active_background_color']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kolor tła kart i treści accordionu</span>
                <input type="text" name="menu_content_background_color" value="<?= e((string) ($form['menu_content_background_color'] ?? '#ffffff')) ?>" placeholder="#ffffff">
                <?php if (!empty($errors['menu_content_background_color'])): ?><small class="form-error"><?= e($errors['menu_content_background_color']) ?></small><?php endif; ?>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="menu_font_capitalize" value="1" <?= checked(!empty($form['menu_font_capitalize'])) ?>>
                <span>Capitalize menu główne</span>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="menu_font_uppercase" value="1" <?= checked(!empty($form['menu_font_uppercase'])) ?>>
                <span>Make UPPERCASE menu główne</span>
            </label>

            <label>
                <span>Rozmiar fontu submenu</span>
                <input type="text" name="submenu_font_size" value="<?= e((string) ($form['submenu_font_size'] ?? 'clamp(1.35rem, 2.7vw, 2.3rem)')) ?>" placeholder="np. clamp(1.35rem, 2.7vw, 2.3rem)">
                <?php if (!empty($errors['submenu_font_size'])): ?><small class="form-error"><?= e($errors['submenu_font_size']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Letter spacing submenu</span>
                <input type="text" name="submenu_font_letter_spacing" value="<?= e((string) ($form['submenu_font_letter_spacing'] ?? '-0.04em')) ?>" placeholder="np. -0.04em">
                <?php if (!empty($errors['submenu_font_letter_spacing'])): ?><small class="form-error"><?= e($errors['submenu_font_letter_spacing']) ?></small><?php endif; ?>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="submenu_font_capitalize" value="1" <?= checked(!empty($form['submenu_font_capitalize'])) ?>>
                <span>Capitalize submenu</span>
            </label>
            <label class="checkbox-field span-2">
                <input type="checkbox" name="submenu_font_uppercase" value="1" <?= checked(!empty($form['submenu_font_uppercase'])) ?>>
                <span>Make UPPERCASE submenu</span>
            </label>
        </div>
        <p class="helper-note">Obsługiwane pliki fontów: <code>.woff2</code>, <code>.woff</code>, <code>.ttf</code>, <code>.otf</code>.</p>
    </section>

    <div class="admin-submit-row">
        <button class="button-primary" type="submit">Zapisz typografię</button>
    </div>
</form>

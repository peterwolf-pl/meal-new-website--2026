<section class="admin-page-head">
    <div>
        <p class="section-kicker">Ustawienia</p>
        <h1>Konfiguracja serwisu</h1>
    </div>
</section>

<?php
$googleConnection ??= [
    'client_configured' => false,
    'callback_url' => '',
    'connected' => false,
    'email' => '',
    'connected_at' => '',
    'ga4_property_id' => '',
    'ga4_measurement_id' => '',
    'search_console_property_url' => '',
    'gtm_container_id' => '',
    'service_account_path' => '',
];
$googleConnectedAt = !empty($googleConnection['connected_at'])
    ? date('Y-m-d H:i', strtotime((string) $googleConnection['connected_at']))
    : null;
?>

<?php if (!empty($errors)): ?>
    <div class="form-error-box">
        <p>Popraw pola formularza zaznaczone poniżej.</p>
    </div>
<?php endif; ?>

<form method="post" action="/admin/settings/save" class="admin-form-stack">
    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">

    <section class="admin-form-card">
        <h2>Dane ogólne</h2>
        <div class="admin-form-grid columns-3">
            <label>
                <span>E-mail kontaktowy</span>
                <input type="email" name="contact_email" value="<?= e($form['contact_email']) ?>" required>
                <?php if (!empty($errors['contact_email'])): ?><small class="form-error"><?= e($errors['contact_email']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Telefon</span>
                <input type="text" name="phone" value="<?= e($form['phone']) ?>" required>
                <?php if (!empty($errors['phone'])): ?><small class="form-error"><?= e($errors['phone']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Miasto</span>
                <input type="text" name="city" value="<?= e($form['city']) ?>" required>
                <?php if (!empty($errors['city'])): ?><small class="form-error"><?= e($errors['city']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Adres</span>
                <input type="text" name="street_address" value="<?= e($form['street_address']) ?>" required>
                <?php if (!empty($errors['street_address'])): ?><small class="form-error"><?= e($errors['street_address']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kod pocztowy</span>
                <input type="text" name="postal_code" value="<?= e($form['postal_code']) ?>" required>
                <?php if (!empty($errors['postal_code'])): ?><small class="form-error"><?= e($errors['postal_code']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Mapa URL</span>
                <input type="url" name="map_url" value="<?= e($form['map_url']) ?>">
            </label>
            <label>
                <span>Instagram URL</span>
                <input type="url" name="instagram_url" value="<?= e($form['instagram_url']) ?>">
            </label>
            <label>
                <span>Facebook URL</span>
                <input type="url" name="facebook_url" value="<?= e($form['facebook_url']) ?>">
            </label>
            <label>
                <span>YouTube URL</span>
                <input type="url" name="youtube_url" value="<?= e($form['youtube_url']) ?>">
            </label>
            <label class="span-3">
                <span>Hero medium</span>
                <select name="hero_media_id">
                    <option value="">- brak -</option>
                    <?php foreach ($mediaAssets as $asset): ?>
                        <option value="<?= e((string) $asset['id']) ?>" <?= ((int) ($form['hero_media_id'] ?? 0) === (int) $asset['id']) ? 'selected' : '' ?>>
                            #<?= e((string) $asset['id']) ?> <?= e($asset['title']) ?> (<?= e($asset['kind']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <h2>Treści PL</h2>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Nazwa muzeum</span>
                <input type="text" name="translations[pl][museum_name]" value="<?= e($form['translations']['pl']['museum_name']) ?>" required>
                <?php if (!empty($errors['translations.pl.museum_name'])): ?><small class="form-error"><?= e($errors['translations.pl.museum_name']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Godziny otwarcia</span>
                <textarea name="translations[pl][opening_hours]" rows="3"><?= e($form['translations']['pl']['opening_hours']) ?></textarea>
            </label>
            <label class="span-2">
                <span>Opis instytucji</span>
                <textarea name="translations[pl][organization_description]" rows="4"><?= e($form['translations']['pl']['organization_description']) ?></textarea>
            </label>
            <label>
                <span>Tytuł strony głównej</span>
                <input type="text" name="translations[pl][homepage_title]" value="<?= e($form['translations']['pl']['homepage_title']) ?>" required>
                <?php if (!empty($errors['translations.pl.homepage_title'])): ?><small class="form-error"><?= e($errors['translations.pl.homepage_title']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Lid strony głównej</span>
                <textarea name="translations[pl][homepage_lead]" rows="3"><?= e($form['translations']['pl']['homepage_lead']) ?></textarea>
            </label>
            <label class="span-2">
                <span>Wstęp strony głównej</span>
                <textarea class="wysiwyg" name="translations[pl][homepage_intro]" rows="10"><?= e($form['translations']['pl']['homepage_intro']) ?></textarea>
            </label>
            <label class="span-2">
                <span>Notatka o wizycie</span>
                <textarea name="translations[pl][visit_note]" rows="3"><?= e($form['translations']['pl']['visit_note']) ?></textarea>
            </label>
            <label>
                <span>Default SEO title</span>
                <input type="text" name="translations[pl][default_seo_title]" value="<?= e($form['translations']['pl']['default_seo_title']) ?>" required>
                <?php if (!empty($errors['translations.pl.default_seo_title'])): ?><small class="form-error"><?= e($errors['translations.pl.default_seo_title']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Default meta description</span>
                <textarea name="translations[pl][default_meta_description]" rows="3"><?= e($form['translations']['pl']['default_meta_description']) ?></textarea>
            </label>
            <label>
                <span>Default OG title</span>
                <input type="text" name="translations[pl][default_og_title]" value="<?= e($form['translations']['pl']['default_og_title']) ?>">
            </label>
            <label>
                <span>Default OG description</span>
                <textarea name="translations[pl][default_og_description]" rows="3"><?= e($form['translations']['pl']['default_og_description']) ?></textarea>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <div class="admin-form-card-head">
            <button
                class="button-secondary"
                type="button"
                data-translate-settings
                data-translate-url="/admin/settings/translate"
                data-loading-label="Tłumaczę..."
            >
                Przetłumacz z PL
            </button>
            <h2>Treści EN</h2>
        </div>
        <p class="helper-note translation-status" data-translate-settings-status hidden></p>
        <p class="helper-note">Przycisk tłumaczy pola z sekcji PL do EN. Pole z edytorem zachowuje formatowanie HTML.</p>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Nazwa muzeum</span>
                <input type="text" name="translations[en][museum_name]" value="<?= e($form['translations']['en']['museum_name']) ?>">
            </label>
            <label>
                <span>Godziny otwarcia</span>
                <textarea name="translations[en][opening_hours]" rows="3"><?= e($form['translations']['en']['opening_hours']) ?></textarea>
            </label>
            <label class="span-2">
                <span>Opis instytucji</span>
                <textarea name="translations[en][organization_description]" rows="4"><?= e($form['translations']['en']['organization_description']) ?></textarea>
            </label>
            <label>
                <span>Tytuł strony głównej</span>
                <input type="text" name="translations[en][homepage_title]" value="<?= e($form['translations']['en']['homepage_title']) ?>">
            </label>
            <label>
                <span>Lid strony głównej</span>
                <textarea name="translations[en][homepage_lead]" rows="3"><?= e($form['translations']['en']['homepage_lead']) ?></textarea>
            </label>
            <label class="span-2">
                <span>Wstęp strony głównej</span>
                <textarea class="wysiwyg" name="translations[en][homepage_intro]" rows="10"><?= e($form['translations']['en']['homepage_intro']) ?></textarea>
            </label>
            <label class="span-2">
                <span>Notatka o wizycie</span>
                <textarea name="translations[en][visit_note]" rows="3"><?= e($form['translations']['en']['visit_note']) ?></textarea>
            </label>
            <label>
                <span>Default SEO title</span>
                <input type="text" name="translations[en][default_seo_title]" value="<?= e($form['translations']['en']['default_seo_title']) ?>">
            </label>
            <label>
                <span>Default meta description</span>
                <textarea name="translations[en][default_meta_description]" rows="3"><?= e($form['translations']['en']['default_meta_description']) ?></textarea>
            </label>
            <label>
                <span>Default OG title</span>
                <input type="text" name="translations[en][default_og_title]" value="<?= e($form['translations']['en']['default_og_title']) ?>">
            </label>
            <label>
                <span>Default OG description</span>
                <textarea name="translations[en][default_og_description]" rows="3"><?= e($form['translations']['en']['default_og_description']) ?></textarea>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <div class="admin-form-card-head">
            <div>
                <h2>Google Analytics / Tag Manager / Search Console</h2>
                <p class="helper-note">Poniżej możesz skonfigurować usługi Google ręcznie. Auto-konfiguracja przez logowanie Google jest opcjonalnym ułatwieniem.</p>
            </div>
            <div class="admin-actions">
                <?php if ($googleConnection['client_configured']): ?>
                    <a class="button-primary" href="/admin/settings/google/connect">
                        <?= $googleConnection['connected'] ? 'Uruchom auto-konfigurację ponownie' : 'Uruchom auto-konfigurację' ?>
                    </a>
                    <?php if ($googleConnection['connected']): ?>
                        <button class="button-secondary" type="submit" formaction="/admin/settings/google/disconnect" formmethod="post">Odłącz konto</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$googleConnection['client_configured']): ?>
            <p class="helper-note">
                Auto-konfiguracja jest opcjonalna. Jeśli chcesz jej używać, ustaw <code>GOOGLE_OAUTH_CLIENT_ID</code> i <code>GOOGLE_OAUTH_CLIENT_SECRET</code>.
                Redirect URI do wpisania w Google Cloud: <strong><?= e($googleConnection['callback_url']) ?></strong>
            </p>
        <?php elseif ($googleConnection['connected']): ?>
            <p class="helper-note">
                Auto-konfiguracja jest połączona<?= !empty($googleConnection['email']) ? ': ' . e($googleConnection['email']) : '.' ?>
                <?php if ($googleConnectedAt): ?>
                    Ostatnia autokonfiguracja: <?= e($googleConnectedAt) ?>.
                <?php endif; ?>
                Ręczne pola poniżej nadal możesz edytować niezależnie.
            </p>
        <?php else: ?>
            <p class="helper-note">
                Jeśli uruchomisz auto-konfigurację, system spróbuje dopasować zasoby po domenie <code><?= e(parse_url((string) ($googleConnection['callback_url'] ?? ''), PHP_URL_HOST) ?: '') ?></code>
                oraz nazwie serwisu. To opcjonalny skrót, a poniższe pola możesz uzupełnić ręcznie bez logowania Google.
            </p>
        <?php endif; ?>

        <div class="admin-form-grid columns-2">
            <label>
                <span>Google Tag Manager Container ID</span>
                <input type="text" name="gtm_container_id" value="<?= e($form['gtm_container_id'] ?? '') ?>" placeholder="GTM-XXXXXXX">
                <?php if (!empty($errors['gtm_container_id'])): ?><small class="form-error"><?= e($errors['gtm_container_id']) ?></small><?php endif; ?>
                <small class="helper-note">
                    Jeśli podasz GTM, to na publicznej stronie będzie ładowany kontener GTM. GTM ma priorytet nad bezpośrednim tagiem GA4.
                    <?php if (!empty($googleConnection['gtm_container_id'])): ?> Auto-wykryte: <?= e($googleConnection['gtm_container_id']) ?>.<?php endif; ?>
                </small>
            </label>
            <label>
                <span>GA4 Measurement ID</span>
                <input type="text" name="ga4_measurement_id" value="<?= e($form['ga4_measurement_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                <?php if (!empty($errors['ga4_measurement_id'])): ?><small class="form-error"><?= e($errors['ga4_measurement_id']) ?></small><?php endif; ?>
                <small class="helper-note">
                    Używane tylko wtedy, gdy GTM nie jest ustawiony.
                    <?php if (!empty($googleConnection['ga4_measurement_id'])): ?> Auto-wykryte: <?= e($googleConnection['ga4_measurement_id']) ?>.<?php endif; ?>
                </small>
            </label>
            <label>
                <span>GA4 Property ID</span>
                <input type="text" name="ga4_property_id" value="<?= e($form['ga4_property_id'] ?? '') ?>" placeholder="123456789">
                <?php if (!empty($errors['ga4_property_id'])): ?><small class="form-error"><?= e($errors['ga4_property_id']) ?></small><?php endif; ?>
                <small class="helper-note">
                    To ID jest potrzebne do pobierania statystyk w CMS przez Google Analytics Data API.
                    <?php if (!empty($googleConnection['ga4_property_id'])): ?> Auto-wykryte: <?= e($googleConnection['ga4_property_id']) ?>.<?php endif; ?>
                </small>
            </label>
            <label>
                <span>Search Console Property</span>
                <input type="text" name="search_console_property_url" value="<?= e($form['search_console_property_url'] ?? '') ?>" placeholder="https://twoja-domena.pl/ lub sc-domain:twoja-domena.pl">
                <?php if (!empty($errors['search_console_property_url'])): ?><small class="form-error"><?= e($errors['search_console_property_url']) ?></small><?php endif; ?>
                <small class="helper-note">
                    Musi dokładnie odpowiadać właściwości dodanej w Search Console.
                    <?php if (!empty($googleConnection['search_console_property_url'])): ?> Auto-wykryte: <?= e($googleConnection['search_console_property_url']) ?>.<?php endif; ?>
                </small>
            </label>
            <label class="span-2">
                <span>Ścieżka do pliku JSON konta serwisowego Google</span>
                <input type="text" name="google_service_account_json_path" value="<?= e($form['google_service_account_json_path'] ?? '') ?>" placeholder="storage/google/service-account.json">
                <small class="helper-note">
                    Umieść plik poza katalogiem publicznym. Konto serwisowe działa jako fallback, jeśli OAuth użytkownika nie jest podłączony albo wygasł.
                    <?php if (!empty($googleConnection['service_account_path'])): ?> Aktualnie ustawiono: <?= e($googleConnection['service_account_path']) ?>.<?php endif; ?>
                </small>
            </label>
        </div>
    </section>

    <div class="admin-submit-row">
        <button class="button-primary" type="submit">Zapisz ustawienia</button>
    </div>
</form>

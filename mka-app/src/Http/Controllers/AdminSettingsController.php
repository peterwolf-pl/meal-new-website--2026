<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;

final class AdminSettingsController extends Controller
{
    public function edit(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $settings = $this->applyIntegrationConfigDefaults(
            $this->app->settingRepository()->getForAdmin() ?? $this->blankForm()
        );

        return $this->form($settings, []);
    }

    public function save(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $settings = $this->app->settingRepository()->getForAdmin() ?? $this->blankForm();
        $form = array_replace($settings, $this->hydrateForm($_POST));
        $errors = $this->validateForm($form);

        if ($errors !== []) {
            return $this->form($form, $errors);
        }

        $this->app->settingRepository()->save($form);
        if ($this->app->settingRepository()->supportsThemeFonts()) {
            $this->app->flash('success', 'Ustawienia zostały zapisane.');
        } else {
            $this->app->flash('error', 'Ustawienia zostały zapisane, ale fonty nie zmienią się dopóki nie dodasz kolumn typografii do tabeli settings.');
        }

        return $this->redirect('/admin/settings');
    }

    public function connectGoogle(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if ($this->app->settingRepository()->getForAdmin() === null) {
            $this->app->flash('error', 'Najpierw zapisz podstawowe ustawienia serwisu, a potem podłącz Google.');

            return $this->redirect('/admin/settings');
        }

        try {
            $state = bin2hex(random_bytes(24));
            $_SESSION['google_oauth_state'] = [
                'value' => $state,
                'created_at' => time(),
            ];

            return $this->redirect($this->app->googleIntegration()->authorizationUrl($state));
        } catch (Throwable $exception) {
            $this->app->flash('error', 'Nie udało się rozpocząć logowania Google: ' . $exception->getMessage());

            return $this->redirect('/admin/settings');
        }
    }

    public function googleCallback(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $oauthState = $_SESSION['google_oauth_state'] ?? null;
        unset($_SESSION['google_oauth_state']);

        if (!is_array($oauthState) || !$this->isValidGoogleOauthState($oauthState, $_GET['state'] ?? null)) {
            $this->app->flash('error', 'Sesja logowania Google wygasła albo ma nieprawidłowy stan. Spróbuj ponownie.');

            return $this->redirect('/admin/settings');
        }

        $googleError = trim((string) ($_GET['error'] ?? ''));
        if ($googleError !== '') {
            $this->app->flash('error', 'Google przerwał logowanie: ' . $googleError . '.');

            return $this->redirect('/admin/settings');
        }

        $authorizationCode = trim((string) ($_GET['code'] ?? ''));
        if ($authorizationCode === '') {
            $this->app->flash('error', 'Google nie zwrócił kodu autoryzacyjnego.');

            return $this->redirect('/admin/settings');
        }

        $settings = $this->app->settingRepository()->getForAdmin();
        if ($settings === null) {
            $this->app->flash('error', 'Najpierw zapisz podstawowe ustawienia serwisu, a potem podłącz Google.');

            return $this->redirect('/admin/settings');
        }

        try {
            $result = $this->app->googleIntegration()->completeOAuthAutoconfiguration($settings, $authorizationCode);
            $saved = $this->app->settingRepository()->updateGoogleAutoconfig($result['settings']);

            if (!$saved) {
                throw new \RuntimeException('Nie udało się zapisać ustawień połączenia Google w bazie danych.');
            }

            $this->app->flash('success', (string) $result['message']);
        } catch (Throwable $exception) {
            $this->app->flash('error', 'Nie udało się zakończyć auto-konfiguracji Google: ' . $exception->getMessage());
        }

        return $this->redirect('/admin/settings');
    }

    public function disconnectGoogle(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        if ($this->app->settingRepository()->clearGoogleOauthConnection()) {
            $this->app->flash('success', 'Połączenie Google OAuth zostało odłączone. Wykryte identyfikatory pozostają w ustawieniach jako ręczna konfiguracja.');
        } else {
            $this->app->flash('error', 'Nie udało się odłączyć konta Google.');
        }

        return $this->redirect('/admin/settings');
    }

    public function translate(): string
    {
        if (!$this->app->auth()->check()) {
            return $this->json([
                'ok' => false,
                'message' => 'Sesja administratora wygasła. Zaloguj się ponownie.',
            ], 401);
        }

        if (!$this->isValidCsrfToken($_POST['_token'] ?? null)) {
            return $this->json([
                'ok' => false,
                'message' => 'Nieprawidłowy token sesji.',
            ], 419);
        }

        $source = $this->hydrateTranslation(is_array($_POST['source'] ?? null) ? $_POST['source'] : []);

        if (!$this->hasTranslatableSource($source)) {
            return $this->json([
                'ok' => false,
                'message' => 'Najpierw uzupełnij pola w sekcji Treści PL.',
            ], 422);
        }

        $translator = $this->app->openAiTranslation();
        if (!$translator->isConfigured()) {
            return $this->json([
                'ok' => false,
                'message' => 'Brakuje konfiguracji OpenAI. Uzupełnij OPENAI_API_KEY lub mka-app/config/local.php.',
            ], 503);
        }

        try {
            $translation = $translator->translateSettingsToEnglish($source);
        } catch (Throwable $exception) {
            return $this->json([
                'ok' => false,
                'message' => 'Nie udało się przetłumaczyć treści: ' . $exception->getMessage(),
            ], 502);
        }

        return $this->json([
            'ok' => true,
            'translation' => $translation,
            'model' => $translator->configuredModel(),
        ]);
    }

    private function form(array $form, array $errors): string
    {
        return $this->render('admin/settings/form', [
            'pageTitle' => 'Ustawienia',
            'form' => $form,
            'errors' => $errors,
            'loadTinyMce' => true,
            'googleConnection' => $this->app->googleIntegration()->oauthConnection($form),
            'mediaAssets' => $this->app->mediaRepository()->allLocalized($this->adminLocale()),
        ], 'admin');
    }

    private function hydrateForm(array $input): array
    {
        $translations = $input['translations'] ?? [];

        return [
            'contact_email' => trim((string) ($input['contact_email'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'street_address' => trim((string) ($input['street_address'] ?? '')),
            'postal_code' => trim((string) ($input['postal_code'] ?? '')),
            'city' => trim((string) ($input['city'] ?? '')),
            'map_url' => trim((string) ($input['map_url'] ?? '')),
            'facebook_url' => trim((string) ($input['facebook_url'] ?? '')),
            'instagram_url' => trim((string) ($input['instagram_url'] ?? '')),
            'youtube_url' => trim((string) ($input['youtube_url'] ?? '')),
            'hero_media_id' => !empty($input['hero_media_id']) ? (int) $input['hero_media_id'] : null,
            'gtm_container_id' => trim((string) ($input['gtm_container_id'] ?? '')),
            'ga4_measurement_id' => trim((string) ($input['ga4_measurement_id'] ?? '')),
            'ga4_property_id' => trim((string) ($input['ga4_property_id'] ?? '')),
            'search_console_property_url' => trim((string) ($input['search_console_property_url'] ?? '')),
            'google_service_account_json_path' => trim((string) ($input['google_service_account_json_path'] ?? '')),
            'translations' => [
                'pl' => $this->hydrateTranslation($translations['pl'] ?? []),
                'en' => $this->hydrateTranslation($translations['en'] ?? []),
            ],
        ];
    }

    private function hydrateTranslation(array $input): array
    {
        return [
            'museum_name' => trim((string) ($input['museum_name'] ?? '')),
            'organization_description' => trim((string) ($input['organization_description'] ?? '')),
            'opening_hours' => trim((string) ($input['opening_hours'] ?? '')),
            'homepage_title' => trim((string) ($input['homepage_title'] ?? '')),
            'homepage_lead' => trim((string) ($input['homepage_lead'] ?? '')),
            'homepage_intro' => $this->app->contentService()->sanitizeRichText((string) ($input['homepage_intro'] ?? '')),
            'visit_note' => trim((string) ($input['visit_note'] ?? '')),
            'default_seo_title' => trim((string) ($input['default_seo_title'] ?? '')),
            'default_meta_description' => trim(strip_tags((string) ($input['default_meta_description'] ?? ''))),
            'default_og_title' => trim((string) ($input['default_og_title'] ?? '')),
            'default_og_description' => trim(strip_tags((string) ($input['default_og_description'] ?? ''))),
        ];
    }

    private function validateForm(array $form): array
    {
        $errors = [];

        foreach (['contact_email', 'phone', 'street_address', 'postal_code', 'city'] as $field) {
            if ($form[$field] === '') {
                $errors[$field] = 'To pole jest wymagane.';
            }
        }

        if ($form['translations']['pl']['museum_name'] === '') {
            $errors['translations.pl.museum_name'] = 'Nazwa muzeum PL jest wymagana.';
        }

        if ($form['translations']['pl']['homepage_title'] === '') {
            $errors['translations.pl.homepage_title'] = 'Tytuł strony głównej PL jest wymagany.';
        }

        if ($form['translations']['pl']['default_seo_title'] === '') {
            $errors['translations.pl.default_seo_title'] = 'Domyślny SEO title PL jest wymagany.';
        }

        if ($form['gtm_container_id'] !== '' && !preg_match('/^GTM-[A-Z0-9]+$/i', $form['gtm_container_id'])) {
            $errors['gtm_container_id'] = 'Podaj poprawny identyfikator kontenera GTM, np. GTM-XXXXXXX.';
        }

        if ($form['ga4_measurement_id'] !== '' && !preg_match('/^G-[A-Z0-9]+$/i', $form['ga4_measurement_id'])) {
            $errors['ga4_measurement_id'] = 'Podaj poprawny Measurement ID GA4, np. G-XXXXXXXXXX.';
        }

        if ($form['ga4_property_id'] !== '' && !preg_match('/^(properties\/)?\d+$/', $form['ga4_property_id'])) {
            $errors['ga4_property_id'] = 'Podaj numeryczny identyfikator właściwości GA4, np. 123456789.';
        }

        if (
            $form['search_console_property_url'] !== ''
            && !preg_match('#^(https?://.+|sc-domain:.+)$#', $form['search_console_property_url'])
        ) {
            $errors['search_console_property_url'] = 'Podaj adres właściwości Search Console, np. https://twoja-domena.pl/ lub sc-domain:twoja-domena.pl.';
        }

        return $errors;
    }

    private function blankForm(): array
    {
        return [
            'contact_email' => '',
            'phone' => '',
            'street_address' => '',
            'postal_code' => '',
            'city' => '',
            'map_url' => '',
            'facebook_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'hero_media_id' => null,
            'gtm_container_id' => '',
            'ga4_measurement_id' => '',
            'ga4_property_id' => '',
            'search_console_property_url' => '',
            'google_service_account_json_path' => '',
            'google_oauth_refresh_token' => '',
            'google_oauth_email' => '',
            'google_oauth_connected_at' => '',
            'translations' => [
                'pl' => [
                    'museum_name' => '',
                    'organization_description' => '',
                    'opening_hours' => '',
                    'homepage_title' => '',
                    'homepage_lead' => '',
                    'homepage_intro' => '',
                    'visit_note' => '',
                    'default_seo_title' => '',
                    'default_meta_description' => '',
                    'default_og_title' => '',
                    'default_og_description' => '',
                ],
                'en' => [
                    'museum_name' => '',
                    'organization_description' => '',
                    'opening_hours' => '',
                    'homepage_title' => '',
                    'homepage_lead' => '',
                    'homepage_intro' => '',
                    'visit_note' => '',
                    'default_seo_title' => '',
                    'default_meta_description' => '',
                    'default_og_title' => '',
                    'default_og_description' => '',
                ],
            ],
        ];
    }

    private function applyIntegrationConfigDefaults(array $form): array
    {
        $defaults = [
            'gtm_container_id' => (string) $this->app->config('integrations.gtm_container_id', ''),
            'ga4_measurement_id' => (string) $this->app->config('integrations.ga4_measurement_id', ''),
            'ga4_property_id' => (string) $this->app->config('integrations.ga4_property_id', ''),
            'search_console_property_url' => (string) $this->app->config('integrations.search_console_property_url', ''),
            'google_service_account_json_path' => (string) $this->app->config('integrations.google_service_account_json_path', ''),
        ];

        foreach ($defaults as $field => $value) {
            if (($form[$field] ?? '') === '' && $value !== '') {
                $form[$field] = $value;
            }
        }

        return $form;
    }

    private function hasTranslatableSource(array $source): bool
    {
        foreach ($source as $value) {
            if (trim(strip_tags((string) $value)) !== '') {
                return true;
            }
        }

        return false;
    }

    private function isValidGoogleOauthState(array $oauthState, mixed $requestState): bool
    {
        $expected = trim((string) ($oauthState['value'] ?? ''));
        $createdAt = (int) ($oauthState['created_at'] ?? 0);
        $provided = trim((string) $requestState);

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return false;
        }

        return $createdAt > 0 && (time() - $createdAt) <= 900;
    }

}

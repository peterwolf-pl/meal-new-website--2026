<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;

final class AdminContentController extends Controller
{
    public function index(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return $this->render('admin/content/index', [
            'pageTitle' => 'Treści',
            'items' => $this->app->contentRepository()->allForAdmin(),
            'navigation' => $this->app->navigation(),
        ], 'admin');
    }

    public function create(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return $this->form($this->blankForm(), [], true);
    }

    public function edit(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $item = $this->app->contentRepository()->findForAdmin($id);
        if (!$item) {
            http_response_code(404);
            return 'Nie znaleziono treści.';
        }

        return $this->form($item, [], false);
    }

    public function save(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $form = $this->hydrateForm($_POST);
        $errors = $this->validateForm($form);

        if ($errors !== []) {
            return $this->form($form, $errors, empty($form['id']));
        }

        try {
            $id = $this->app->contentRepository()->save($form);
        } catch (Throwable $exception) {
            $this->app->flash('error', 'Nie udało się zapisać treści: ' . $exception->getMessage());

            return $this->form($form, [], empty($form['id']));
        }

        $this->app->flash('success', 'Treść została zapisana.');

        return $this->redirect('/admin/content/' . $id);
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
                'message' => 'Najpierw uzupełnij pola w sekcji Treść PL.',
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
            $translation = $translator->translateContentToEnglish($source, [
                'content_type' => strtoupper((string) ($_POST['content_type'] ?? 'PAGE')),
                'section_key' => strtoupper((string) ($_POST['section_key'] ?? 'MUZEUM')),
            ]);
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

    public function delete(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $this->app->contentRepository()->delete($id);
        $this->app->flash('success', 'Treść została usunięta.');

        return $this->redirect('/admin/content');
    }

    private function form(array $form, array $errors, bool $isNew): string
    {
        return $this->render('admin/content/form', [
            'pageTitle' => $isNew ? 'Nowa treść' : 'Edycja treści',
            'form' => $form,
            'errors' => $errors,
            'isNew' => $isNew,
            'loadTinyMce' => true,
            'contentTypes' => $this->app->contentService()->contentTypeOptions(),
            'sectionOptions' => $this->app->contentService()->sectionOptions(),
            'statusOptions' => $this->app->contentService()->statusOptions(),
            'mediaAssets' => $this->app->mediaRepository()->allLocalized($this->adminLocale()),
            'supportsEmployeeProjects' => $this->app->contentRepository()->supportsEmployeeProjects(),
        ], 'admin');
    }

    private function hydrateForm(array $input): array
    {
        $translations = $input['translations'] ?? [];

        $form = [
            'id' => !empty($input['id']) ? (int) $input['id'] : null,
            'content_type' => strtoupper((string) ($input['content_type'] ?? 'PAGE')),
            'section_key' => strtoupper((string) ($input['section_key'] ?? 'MUZEUM')),
            'status' => strtolower((string) ($input['status'] ?? 'draft')),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'featured' => !empty($input['featured']) ? 1 : 0,
            'published_at' => trim((string) ($input['published_at'] ?? '')),
            'event_start' => trim((string) ($input['event_start'] ?? '')),
            'event_end' => trim((string) ($input['event_end'] ?? '')),
            'event_location' => trim((string) ($input['event_location'] ?? '')),
            'registration_url' => trim((string) ($input['registration_url'] ?? '')),
            'collection_group' => trim((string) ($input['collection_group'] ?? '')),
            'creator_name' => trim((string) ($input['creator_name'] ?? '')),
            'item_year' => trim((string) ($input['item_year'] ?? '')),
            'contact_email' => trim((string) ($input['contact_email'] ?? '')),
            'media_ids' => array_map('intval', $input['media_ids'] ?? []),
            'translations' => [
                'pl' => $this->hydrateTranslation($translations['pl'] ?? []),
                'en' => $this->hydrateTranslation($translations['en'] ?? []),
            ],
        ];

        if ($form['translations']['pl']['slug'] === '' && $form['translations']['pl']['title'] !== '') {
            $form['translations']['pl']['slug'] = $this->app->contentService()->normalizeSlug($form['translations']['pl']['title']);
        }

        if ($form['translations']['en']['slug'] === '' && $form['translations']['en']['title'] !== '') {
            $form['translations']['en']['slug'] = $this->app->contentService()->normalizeSlug($form['translations']['en']['title']);
        }

        if ($form['status'] === 'published' && $form['published_at'] === '') {
            $form['published_at'] = date('Y-m-d H:i:s');
        }

        return $form;
    }

    private function hydrateTranslation(array $input): array
    {
        return [
            'slug' => $this->app->contentService()->normalizeSlug((string) ($input['slug'] ?? '')),
            'title' => trim((string) ($input['title'] ?? '')),
            'summary' => trim(strip_tags((string) ($input['summary'] ?? ''))),
            'body' => $this->app->contentService()->sanitizeRichText((string) ($input['body'] ?? '')),
            'employee_projects' => trim(strip_tags((string) ($input['employee_projects'] ?? ''))),
            'seo_keywords' => trim((string) ($input['seo_keywords'] ?? '')),
            'seo_title' => trim((string) ($input['seo_title'] ?? '')),
            'meta_description' => trim(strip_tags((string) ($input['meta_description'] ?? ''))),
            'og_title' => trim((string) ($input['og_title'] ?? '')),
            'og_description' => trim(strip_tags((string) ($input['og_description'] ?? ''))),
        ];
    }

    private function validateForm(array $form): array
    {
        $errors = [];

        if (!in_array($form['content_type'], $this->app->contentService()->contentTypeOptions(), true)) {
            $errors['content_type'] = 'Nieprawidłowy typ treści.';
        }

        if (!in_array($form['section_key'], $this->app->contentService()->sectionOptions(), true)) {
            $errors['section_key'] = 'Nieprawidłowa sekcja.';
        }

        if (!in_array($form['status'], $this->app->contentService()->statusOptions(), true)) {
            $errors['status'] = 'Nieprawidłowy status publikacji.';
        }

        if ($form['translations']['pl']['title'] === '') {
            $errors['translations.pl.title'] = 'Tytuł PL jest wymagany.';
        }

        if ($form['translations']['pl']['slug'] === '') {
            $errors['translations.pl.slug'] = 'Slug PL jest wymagany.';
        }

        if ($form['translations']['pl']['body'] === '') {
            $errors['translations.pl.body'] = 'Treść PL jest wymagana.';
        }

        foreach (['pl', 'en'] as $locale) {
            $translation = $form['translations'][$locale];
            if ($translation['slug'] !== '' && $this->app->contentRepository()->slugExists($locale, $translation['slug'], $form['id'])) {
                $errors["translations.{$locale}.slug"] = 'Ten slug jest już zajęty dla wybranego języka.';
            }
        }

        if (in_array($form['content_type'], ['EXHIBITION', 'EVENT'], true) && $form['event_start'] === '') {
            $errors['event_start'] = 'Dla wydarzeń i wystaw podaj datę rozpoczęcia.';
        }

        if ($form['content_type'] === 'COLLECTION' && $form['creator_name'] === '') {
            $errors['creator_name'] = 'Dla obiektu kolekcji podaj autora.';
        }

        if ($form['content_type'] === 'EMPLOYEE') {
            if ($form['section_key'] !== 'MUZEUM') {
                $errors['section_key'] = 'Pracownik powinien być zapisany w sekcji Muzeum.';
            }

            if ($form['translations']['pl']['summary'] === '') {
                $errors['translations.pl.summary'] = 'Dla pracownika podaj rolę lub zakres obowiązków.';
            }

            if ($form['contact_email'] === '') {
                $errors['contact_email'] = 'Dla pracownika podaj adres kontaktowy.';
            } elseif (!filter_var($form['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['contact_email'] = 'Podaj poprawny adres e-mail.';
            }

            if ($form['media_ids'] === []) {
                $errors['media_ids'] = 'Dla pracownika wybierz co najmniej jedno zdjęcie.';
            }
        }

        return $errors;
    }

    private function blankForm(): array
    {
        return [
            'id' => null,
            'content_type' => 'PAGE',
            'section_key' => 'MUZEUM',
            'status' => 'draft',
            'sort_order' => 0,
            'featured' => 0,
            'published_at' => '',
            'event_start' => '',
            'event_end' => '',
            'event_location' => '',
            'registration_url' => '',
            'collection_group' => '',
            'creator_name' => '',
            'item_year' => '',
            'contact_email' => '',
            'media_ids' => [],
            'translations' => [
                'pl' => [
                    'slug' => '',
                    'title' => '',
                    'summary' => '',
                    'body' => '',
                    'employee_projects' => '',
                    'seo_keywords' => '',
                    'seo_title' => '',
                    'meta_description' => '',
                    'og_title' => '',
                    'og_description' => '',
                ],
                'en' => [
                    'slug' => '',
                    'title' => '',
                    'summary' => '',
                    'body' => '',
                    'employee_projects' => '',
                    'seo_keywords' => '',
                    'seo_title' => '',
                    'meta_description' => '',
                    'og_title' => '',
                    'og_description' => '',
                ],
            ],
        ];
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
}

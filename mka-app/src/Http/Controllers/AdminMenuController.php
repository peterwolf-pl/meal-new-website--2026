<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;

final class AdminMenuController extends Controller
{
    public function index(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return $this->render('admin/menu', [
            'pageTitle' => 'Układ menu',
            'items' => $this->app->navigationRepository()->tree('pl'),
            'parentOptions' => $this->parentOptions(),
            'appearance' => $this->appearanceForm(),
            'form' => $this->blankForm(),
            'editing' => null,
            'errors' => [],
        ], 'admin');
    }

    public function edit(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $item = $this->app->navigationRepository()->find($id);
        if (!$item) {
            return $this->redirect('/admin/menu');
        }

        return $this->render('admin/menu', [
            'pageTitle' => 'Układ menu',
            'items' => $this->app->navigationRepository()->tree('pl'),
            'parentOptions' => $this->parentOptions(),
            'appearance' => $this->appearanceForm(),
            'form' => $item,
            'editing' => $item,
            'errors' => [],
        ], 'admin');
    }

    public function saveAppearance(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $settings = $this->app->settingRepository()->getForAdmin() ?? [];
        $color = trim((string) ($_POST['menu_active_background_color'] ?? '#ededed'));
        $lineColor = trim((string) ($_POST['menu_line_color'] ?? '#cbbfb0'));

        if (!preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            $this->app->flash('error', 'Podaj poprawny kolor tła aktywnego kafelka w formacie HEX.');

            return $this->redirect('/admin/menu');
        }

        if (!preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $lineColor)) {
            $this->app->flash('error', 'Podaj poprawny kolor linii menu w formacie HEX.');

            return $this->redirect('/admin/menu');
        }

        $settings['menu_active_background_color'] = $color;
        $settings['menu_line_color'] = $lineColor;
        $this->app->settingRepository()->save($settings);
        $this->app->flash('success', 'Wygląd menu został zapisany.');

        return $this->redirect('/admin/menu');
    }

    public function save(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $form = $this->hydrate($_POST);
        $errors = $this->validate($form);

        if ($errors !== []) {
            return $this->render('admin/menu', [
                'pageTitle' => 'Układ menu',
                'items' => $this->app->navigationRepository()->tree('pl'),
                'parentOptions' => $this->parentOptions(),
                'form' => $form,
                'editing' => !empty($form['id']) ? $form : null,
                'errors' => $errors,
            ], 'admin');
        }

        $this->app->navigationRepository()->save($form);
        $this->app->flash('success', 'Układ menu został zapisany.');

        return $this->redirect('/admin/menu');
    }

    public function delete(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $this->app->navigationRepository()->delete($id);
        $this->app->flash('success', 'Pozycja menu została usunięta.');

        return $this->redirect('/admin/menu');
    }

    public function moveUp(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $this->app->navigationRepository()->moveUp($id);
        $this->app->flash('success', 'Pozycja została przesunięta w górę.');

        return $this->redirect('/admin/menu');
    }

    public function moveDown(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $this->app->navigationRepository()->moveDown($id);
        $this->app->flash('success', 'Pozycja została przesunięta w dół.');

        return $this->redirect('/admin/menu');
    }

    public function setVisibility(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $item = $this->app->navigationRepository()->find($id);
        if (!$item) {
            $this->app->flash('error', 'Nie znaleziono wskazanej pozycji menu.');

            return $this->redirect('/admin/menu');
        }

        $isVisible = !empty($_POST['is_visible']);
        $this->app->navigationRepository()->setVisibility($id, $isVisible);
        $this->app->flash(
            'success',
            $isVisible
                ? 'Pozycja menu została pokazana na stronie.'
                : 'Pozycja menu została ukryta na stronie.'
        );

        return $this->redirect('/admin/menu');
    }

    public function reorder(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $payload = json_decode((string) ($_POST['order_payload'] ?? '[]'), true);
        if (!is_array($payload)) {
            $this->app->flash('error', 'Nieprawidłowy payload kolejności.');

            return $this->redirect('/admin/menu');
        }

        $rows = [];
        foreach ($payload as $row) {
            if (!is_array($row) || empty($row['id'])) {
                continue;
            }

            $rows[] = [
                'id' => (int) $row['id'],
                'parent_id' => !empty($row['parent_id']) ? (int) $row['parent_id'] : null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];
        }

        $this->app->navigationRepository()->reorder($rows);
        $this->app->flash('success', 'Układ menu został zaktualizowany.');

        return $this->redirect('/admin/menu');
    }

    public function importCurrent(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $this->app->navigationRepository()->replaceLocaleWithGroups(
            'pl',
            $this->app->navigation()->defaultGroups('pl')
        );
        $this->app->flash('success', 'Obecny układ menu został zaimportowany do CMS.');

        return $this->redirect('/admin/menu');
    }

    public function syncToEnglish(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $items = $this->app->navigationRepository()->tree('pl');
        if ($items === []) {
            $this->app->flash('error', 'Najpierw utwórz układ menu PL.');

            return $this->redirect('/admin/menu');
        }

        $translator = $this->app->openAiTranslation();
        if (!$translator->isConfigured()) {
            $this->app->flash('error', 'Brakuje konfiguracji OpenAI do tłumaczenia menu.');

            return $this->redirect('/admin/menu');
        }

        try {
            $translatedLabels = $translator->translateMenuItemsToEnglish($this->flattenMenuTranslations($items));
            $englishTree = $this->buildEnglishTree($items, $translatedLabels);
            $this->app->navigationRepository()->replaceLocaleWithTree('en', $englishTree);
        } catch (Throwable $exception) {
            $this->app->flash('error', 'Nie udało się zsynchronizować menu EN: ' . $exception->getMessage());

            return $this->redirect('/admin/menu');
        }

        $this->app->flash('success', 'Menu EN zostało zsynchronizowane z PL i przetłumaczone.');

        return $this->redirect('/admin/menu');
    }

    private function hydrate(array $input): array
    {
        return [
            'id' => !empty($input['id']) ? (int) $input['id'] : null,
            'parent_id' => !empty($input['parent_id']) ? (int) $input['parent_id'] : null,
            'locale' => 'pl',
            'label' => trim((string) ($input['label'] ?? '')),
            'title' => trim((string) ($input['title'] ?? '')),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'href' => trim((string) ($input['href'] ?? '')),
            'item_kind' => trim((string) ($input['item_kind'] ?? 'link')),
            'section_key' => trim((string) ($input['section_key'] ?? '')),
            'content_type' => trim((string) ($input['content_type'] ?? '')),
            'sort_order' => isset($input['sort_order']) && $input['sort_order'] !== ''
                ? (int) $input['sort_order']
                : null,
            'is_visible' => !empty($input['is_visible']) ? 1 : 0,
        ];
    }

    private function validate(array $form): array
    {
        $errors = [];

        if ($form['label'] === '') {
            $errors['label'] = 'Etykieta jest wymagana.';
        }

        if (!in_array($form['item_kind'], ['page', 'listing', 'link'], true)) {
            $errors['item_kind'] = 'Wybierz poprawny typ pozycji.';
        }

        return $errors;
    }

    private function parentOptions(): array
    {
        return array_values(array_filter(
            $this->app->navigationRepository()->all('pl'),
            static fn(array $item): bool => empty($item['parent_id'])
        ));
    }

    private function blankForm(): array
    {
        return [
            'id' => null,
            'parent_id' => null,
            'label' => '',
            'title' => '',
            'slug' => '',
            'href' => '',
            'item_kind' => 'link',
            'section_key' => '',
            'content_type' => '',
            'sort_order' => null,
            'is_visible' => 1,
        ];
    }

    private function appearanceForm(): array
    {
        $settings = $this->app->settingRepository()->getForAdmin() ?? [];

        return [
            'menu_active_background_color' => (string) ($settings['menu_active_background_color'] ?? '#ededed'),
            'menu_line_color' => (string) ($settings['menu_line_color'] ?? '#cbbfb0'),
        ];
    }

    private function flattenMenuTranslations(array $items): array
    {
        $flat = [];

        $walk = function (array $nodes) use (&$walk, &$flat): void {
            foreach ($nodes as $node) {
                $id = (int) ($node['id'] ?? 0);
                if ($id > 0) {
                    $flat[$id] = [
                        'id' => $id,
                        'label' => (string) ($node['label'] ?? ''),
                        'title' => (string) ($node['title'] ?? ''),
                        'slug' => (string) ($node['slug'] ?? ''),
                    ];
                }

                $children = $node['children'] ?? [];
                if (is_array($children) && $children !== []) {
                    $walk($children);
                }
            }
        };

        $walk($items);

        return $flat;
    }

    private function buildEnglishTree(array $items, array $translatedLabels): array
    {
        $build = function (array $nodes) use (&$build, $translatedLabels): array {
            $result = [];

            foreach ($nodes as $node) {
                $id = (int) ($node['id'] ?? 0);
                $translation = $translatedLabels[$id] ?? [];
                $itemKind = (string) ($node['item_kind'] ?? 'link');
                $sectionKey = (string) ($node['section_key'] ?? '');
                $slug = $this->englishMenuSlug($node, $translation);
                $href = $itemKind === 'link'
                    ? $this->switchHrefLocale((string) ($node['href'] ?? ''), 'en')
                    : '';

                $result[] = [
                    'label' => (string) ($translation['label'] ?? ($node['label'] ?? '')),
                    'title' => (string) ($translation['title'] ?? ($node['title'] ?? '')),
                    'slug' => $slug,
                    'href' => $href,
                    'item_kind' => $itemKind,
                    'section_key' => $sectionKey,
                    'content_type' => (string) ($node['content_type'] ?? ''),
                    'sort_order' => (int) ($node['sort_order'] ?? 0),
                    'is_visible' => !empty($node['is_visible']) ? 1 : 0,
                    'children' => $build(is_array($node['children'] ?? null) ? $node['children'] : []),
                ];
            }

            return $result;
        };

        return $build($items);
    }

    private function englishMenuSlug(array $node, array $translation): string
    {
        $itemKind = (string) ($node['item_kind'] ?? 'link');
        $sectionKey = (string) ($node['section_key'] ?? '');
        $slug = trim((string) ($node['slug'] ?? ''));
        $translatedSlug = trim((string) ($translation['slug'] ?? ''));

        if ($slug === '') {
            return $slug;
        }

        if ($itemKind === 'link') {
            return $translatedSlug !== '' ? $translatedSlug : $slug;
        }

        if ($itemKind !== 'page') {
            return $slug;
        }

        if (in_array($sectionKey, ['SHOP', 'VISIT', 'CONTACT'], true)) {
            return $slug;
        }

        return $this->app->contentRepository()->findAlternatePageSlug($sectionKey, $slug, 'pl', 'en')
            ?? ($translatedSlug !== '' ? $translatedSlug : $slug);
    }

    private function switchHrefLocale(string $href, string $targetLocale): string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        return preg_replace('#^/(pl|en)(?=/|$)#', '/' . $targetLocale, $href) ?? $href;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class SiteController extends Controller
{
    public function root(): string
    {
        return $this->redirect('/pl');
    }

    public function home(string $locale): string
    {
        return $this->renderAccordion($locale);
    }

    public function sectionPage(string $locale, string $sectionKey, string $slug): string
    {
        return $this->renderAccordion($locale, $sectionKey, $slug);
    }

    public function singlePage(string $locale, string $sectionKey, string $slug): string
    {
        return $this->renderAccordion($locale, $sectionKey, $slug);
    }

    public function programListing(string $locale, string $type, string $introSlug): string
    {
        return $this->renderAccordion($locale, 'PROGRAM', $introSlug, $type);
    }

    public function collectionListing(string $locale): string
    {
        return $this->renderAccordion($locale, 'ART_BOOK', 'kolekcja', 'COLLECTION');
    }

    public function galleryListing(string $locale): string
    {
        return $this->renderAccordion($locale, 'ART_BOOK', 'galeria', 'GALLERY');
    }

    public function detail(string $locale, string $type, string $slug): string
    {
        $target = $this->detailTarget($type);

        if ($target === null) {
            http_response_code(404);

            return $this->notFound();
        }

        return $this->renderAccordion($locale, $target['section'], $target['child'], $type, $slug);
    }

    public function media(string $path): string
    {
        $path = ltrim($path, '/');
        $file = $this->findMediaFile($path);

        if ($file === null) {
            http_response_code(404);

            return 'Nie znaleziono pliku.';
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($file));
        readfile($file);

        return '';
    }

    private function findMediaFile(string $path): ?string
    {
        $roots = [
            $this->app->path('uploads'),
            dirname($this->app->path('root')) . '/upload',
            $this->app->path('root') . '/storage/uploads',
            dirname($this->app->path('root')) . '/public_html/storage/uploads',
        ];

        foreach ($roots as $root) {
            $base = realpath($root);
            if (!$base) {
                continue;
            }

            $file = realpath($base . '/' . $path);
            if ($file && str_starts_with($file, $base) && is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    public function notFound(string $message = 'Nie znaleziono strony.'): string
    {
        http_response_code(404);
        $settings = $this->app->settingRepository()->getLocalized('pl');

        return $this->render('site/404', [
            'pageTitle' => '404',
            'locale' => 'pl',
            'settings' => $settings,
            'navGroups' => $this->app->navigation()->groups('pl'),
            'breadcrumbs' => [],
            'seo' => [
                'title' => '404 | Muzeum Książki Artystycznej',
                'description' => $message,
                'og_title' => '404 | Muzeum Książki Artystycznej',
                'og_description' => $message,
                'canonical' => $this->app->baseUrl('/404'),
                'image' => $settings['hero_media']['public_url'] ?? null,
            ],
        ]);
    }

    private function renderAccordion(
        string $locale,
        ?string $activeSection = null,
        ?string $activeChild = null,
        ?string $activeItemType = null,
        ?string $activeItemSlug = null
    ): string {
        $locale = $this->app->localeOrDefault($locale);
        $settings = $this->settings($locale);
        $navGroups = $this->app->navigation()->groups($locale);
        $accordion = $this->buildAccordion($navGroups, $locale, $activeSection, $activeChild, $activeItemType, $activeItemSlug);

        if ($accordion['missing_detail']) {
            http_response_code(404);

            return $this->notFound();
        }

        $activeContext = $accordion['active_context'];
        $pageTitle = $activeContext['title']
            ?? ($settings['translation']['homepage_title'] ?: $settings['translation']['museum_name']);

        return $this->render('site/accordion', [
            'pageTitle' => $pageTitle,
            'locale' => $locale,
            'settings' => $settings,
            'navGroups' => $navGroups,
            'seo' => $activeContext
                ? $this->app->contentService()->seoForEntry($activeContext, $settings, $locale)
                : $this->app->contentService()->seoForHome($settings, $locale),
            'accordionSections' => $accordion['sections'],
            'accordionActive' => $accordion['active'],
        ]);
    }

    private function buildAccordion(
        array $groups,
        string $locale,
        ?string $activeSection,
        ?string $activeChild,
        ?string $activeItemType,
        ?string $activeItemSlug
    ): array {
        $sections = [];
        $activeContext = null;
        $missingDetail = false;

        foreach ($groups as $group) {
            $group['is_active'] = $group['key'] === $activeSection;
            $group['has_subnav'] = count($group['children']) > 1;
            $group['active_child'] = null;
            $children = [];

            foreach ($group['children'] as $child) {
                $child['is_active'] = $group['is_active'] && $child['slug'] === $activeChild;

                if ($child['kind'] === 'listing') {
                    $intro = $this->contentEntry(
                        $this->app->contentRepository()->findPage($child['section_key'], $child['slug'], $locale),
                        $child
                    );
                    $child['intro'] = $intro;
                    $child['items'] = $this->app->contentRepository()->listByType($child['content_type'], $locale);
                    $child['selected_item'] = null;

                    if ($child['is_active'] && $activeItemSlug !== null) {
                        $selected = $this->app->contentRepository()->findDetail($child['content_type'], $activeItemSlug, $locale);

                        if ($selected === null) {
                            $missingDetail = true;
                        } else {
                            $child['selected_item'] = $this->contentEntry($selected, [
                                'title' => $selected['title'] ?? $child['title'],
                                'slug' => $activeItemSlug,
                                'section_key' => $child['section_key'],
                                'kind' => 'listing',
                                'content_type' => $child['content_type'],
                            ]);
                        }
                    }

                    if ($child['is_active']) {
                        $activeContext = $child['selected_item'] ?? $intro;
                    }
                } else {
                    $child['entry'] = $this->contentEntry(
                        $this->app->contentRepository()->findPage($child['section_key'], $child['slug'], $locale),
                        $child
                    );

                    if ($child['section_key'] === 'MUZEUM' && $child['slug'] === 'zespol') {
                        $child['employees'] = array_map(
                            fn(array $employee): array => $this->contentEntry($employee, [
                                'title' => $employee['title'] ?? '',
                                'slug' => $employee['slug'] ?? '',
                                'section_key' => 'MUZEUM',
                                'kind' => 'page',
                                'content_type' => 'EMPLOYEE',
                            ]),
                            $this->app->contentRepository()->listEmployees($locale)
                        );
                    } else {
                        $child['employees'] = [];
                    }

                    if ($child['is_active']) {
                        $activeContext = $child['entry'];
                    }
                }

                if ($child['is_active']) {
                    $group['active_child'] = $child;
                }

                $children[] = $child;
            }

            $group['children'] = $children;
            $sections[] = $group;
        }

        return [
            'sections' => $sections,
            'active' => [
                'section' => $activeSection,
                'child' => $activeChild,
                'item_type' => $activeItemType,
                'item_slug' => $activeItemSlug,
            ],
            'active_context' => $activeContext,
            'missing_detail' => $missingDetail,
        ];
    }

    private function contentEntry(?array $entry, array $fallback): array
    {
        $entry ??= [];

        return [
            'id' => $entry['id'] ?? null,
            'slug' => $entry['slug'] ?? ($fallback['slug'] ?? ''),
            'title' => $entry['title'] ?? ($fallback['title'] ?? ''),
            'summary' => $entry['summary'] ?? '',
            'body' => trim((string) ($entry['body'] ?? '')) !== ''
                ? (string) $entry['body']
                : '<p>Treść w przygotowaniu.</p>',
            'seo_title' => $entry['seo_title'] ?? ($entry['title'] ?? ($fallback['title'] ?? '')),
            'meta_description' => $entry['meta_description'] ?? ($entry['summary'] ?? ''),
            'og_title' => $entry['og_title'] ?? ($entry['seo_title'] ?? ($entry['title'] ?? ($fallback['title'] ?? ''))),
            'og_description' => $entry['og_description'] ?? ($entry['meta_description'] ?? ($entry['summary'] ?? '')),
            'content_type' => $entry['content_type'] ?? ($fallback['content_type'] ?? 'PAGE'),
            'section_key' => $entry['section_key'] ?? ($fallback['section_key'] ?? ''),
            'event_start' => $entry['event_start'] ?? null,
            'event_end' => $entry['event_end'] ?? null,
            'event_location' => $entry['event_location'] ?? null,
            'registration_url' => $entry['registration_url'] ?? null,
            'collection_group' => $entry['collection_group'] ?? null,
            'creator_name' => $entry['creator_name'] ?? null,
            'item_year' => $entry['item_year'] ?? null,
            'contact_email' => $entry['contact_email'] ?? null,
            'employee_projects' => $entry['employee_projects'] ?? null,
            'media' => $entry['media'] ?? [],
        ];
    }

    private function detailTarget(string $type): ?array
    {
        return match ($type) {
            'EXHIBITION' => ['section' => 'PROGRAM', 'child' => 'wystawy'],
            'EVENT' => ['section' => 'PROGRAM', 'child' => 'wydarzenia'],
            'PROJECT' => ['section' => 'PROGRAM', 'child' => 'projekty'],
            'COLLECTION' => ['section' => 'ART_BOOK', 'child' => 'kolekcja'],
            'GALLERY' => ['section' => 'ART_BOOK', 'child' => 'galeria'],
            default => null,
        };
    }

    private function settings(string $locale): array
    {
        return $this->app->settingRepository()->getLocalized($locale)
            ?? $this->app->settingRepository()->getLocalized($this->app->defaultLocale())
            ?? [
                'translation' => [
                    'museum_name' => 'Muzeum Książki Artystycznej',
                    'organization_description' => '',
                    'opening_hours' => '',
                    'homepage_title' => 'Muzeum Książki Artystycznej',
                    'homepage_lead' => '',
                    'homepage_intro' => '',
                    'visit_note' => '',
                    'default_seo_title' => 'Muzeum Książki Artystycznej',
                    'default_meta_description' => '',
                    'default_og_title' => 'Muzeum Książki Artystycznej',
                    'default_og_description' => '',
                ],
                'hero_media' => null,
                'body_font_preset' => 'editorial-sans',
                'body_font_media' => null,
                'heading_font_preset' => 'editorial-sans',
                'heading_font_media' => null,
                'menu_font_preset' => 'editorial-sans',
                'menu_font_media' => null,
            ];
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\App;

final class ContentService
{
    public function __construct(private readonly App $app)
    {
    }

    public function relativeUrl(array $entry, string $locale = 'pl'): string
    {
        $slug = $entry['slug'] ?? '';
        $section = $entry['section_key'] ?? '';
        $type = $entry['content_type'] ?? '';
        $root = '/' . $locale;

        return match ($type) {
            'EXHIBITION' => $root . '/program/wystawy/' . $slug,
            'EVENT' => $root . '/program/wydarzenia/' . $slug,
            'PROJECT' => $root . '/program/projekty/' . $slug,
            'COLLECTION' => $root . '/ksiazka-artystyczna/kolekcja/' . $slug,
            'GALLERY' => $root . '/ksiazka-artystyczna/galeria/' . $slug,
            default => $this->pageUrl($section, $slug, $locale),
        };
    }

    public function pageUrl(string $section, string $slug, string $locale = 'pl'): string
    {
        $root = '/' . $locale;

        return match ($section) {
            'MUZEUM' => $root . '/muzeum/' . $slug,
            'WARSZTAT' => $root . '/warsztat/' . $slug,
            'EDUKACJA' => $root . '/edukacja/' . $slug,
            'SHOP' => $root . '/sklep',
            'VISIT' => $root . '/wizyta',
            'CONTACT' => $root . '/kontakt',
            'PROGRAM' => $root . '/program/' . $slug,
            'ART_BOOK' => match ($slug) {
                'kolekcja' => $root . '/ksiazka-artystyczna/kolekcja',
                'galeria' => $root . '/ksiazka-artystyczna/galeria',
                default => $root . '/ksiazka-artystyczna',
            },
            default => $root,
        };
    }

    public function breadcrumbsForPage(array $entry, string $locale = 'pl'): array
    {
        $root = '/' . $locale;
        $breadcrumbs = [
            ['label' => 'Strona główna', 'href' => $root],
        ];

        $section = $entry['section_key'] ?? '';
        $title = $entry['title'] ?? '';

        if (in_array($section, ['SHOP', 'VISIT', 'CONTACT'], true)) {
            $breadcrumbs[] = ['label' => $title, 'href' => $this->relativeUrl($entry, $locale)];
            return $breadcrumbs;
        }

        if ($section === 'ART_BOOK' && ($entry['slug'] ?? '') !== 'ksiazka-artystyczna') {
            $breadcrumbs[] = ['label' => 'Książka artystyczna', 'href' => $root . '/ksiazka-artystyczna'];
            $breadcrumbs[] = ['label' => $title, 'href' => $this->relativeUrl($entry, $locale)];
            return $breadcrumbs;
        }

        if ($section === 'PROGRAM') {
            $breadcrumbs[] = ['label' => 'Program', 'href' => $root . '/program/' . ($entry['slug'] ?? '')];
            return $breadcrumbs;
        }

        $breadcrumbs[] = ['label' => $this->app->navigation()->sectionLabel($section), 'href' => $this->relativeUrl($entry, $locale)];
        $breadcrumbs[] = ['label' => $title, 'href' => $this->relativeUrl($entry, $locale)];

        return $breadcrumbs;
    }

    public function breadcrumbsForListing(string $label, string $href, string $locale = 'pl'): array
    {
        return [
            ['label' => 'Strona główna', 'href' => '/' . $locale],
            ['label' => $label, 'href' => $href],
        ];
    }

    public function breadcrumbsForDetail(array $entry, string $locale = 'pl'): array
    {
        $root = '/' . $locale;
        $type = $entry['content_type'];

        return match ($type) {
            'EXHIBITION' => [
                ['label' => 'Strona główna', 'href' => $root],
                ['label' => 'Program', 'href' => $root . '/program/wystawy'],
                ['label' => 'Wystawy', 'href' => $root . '/program/wystawy'],
                ['label' => $entry['title'], 'href' => $this->relativeUrl($entry, $locale)],
            ],
            'EVENT' => [
                ['label' => 'Strona główna', 'href' => $root],
                ['label' => 'Program', 'href' => $root . '/program/wydarzenia'],
                ['label' => 'Wydarzenia', 'href' => $root . '/program/wydarzenia'],
                ['label' => $entry['title'], 'href' => $this->relativeUrl($entry, $locale)],
            ],
            'PROJECT' => [
                ['label' => 'Strona główna', 'href' => $root],
                ['label' => 'Program', 'href' => $root . '/program/projekty'],
                ['label' => 'Projekty', 'href' => $root . '/program/projekty'],
                ['label' => $entry['title'], 'href' => $this->relativeUrl($entry, $locale)],
            ],
            'COLLECTION' => [
                ['label' => 'Strona główna', 'href' => $root],
                ['label' => 'Książka artystyczna', 'href' => $root . '/ksiazka-artystyczna'],
                ['label' => 'Kolekcja MKA i CDA', 'href' => $root . '/ksiazka-artystyczna/kolekcja'],
                ['label' => $entry['title'], 'href' => $this->relativeUrl($entry, $locale)],
            ],
            'GALLERY' => [
                ['label' => 'Strona główna', 'href' => $root],
                ['label' => 'Książka artystyczna', 'href' => $root . '/ksiazka-artystyczna'],
                ['label' => 'Galeria', 'href' => $root . '/ksiazka-artystyczna/galeria'],
                ['label' => $entry['title'], 'href' => $this->relativeUrl($entry, $locale)],
            ],
            default => $this->breadcrumbsForPage($entry, $locale),
        };
    }

    public function seoForHome(array $settings, string $locale = 'pl'): array
    {
        $translation = $settings['translation'];
        $path = '/' . $locale;

        return [
            'title' => $translation['default_seo_title'] ?: $translation['museum_name'],
            'description' => $translation['default_meta_description'] ?: $translation['organization_description'],
            'og_title' => $translation['default_og_title'] ?: $translation['default_seo_title'],
            'og_description' => $translation['default_og_description'] ?: $translation['default_meta_description'],
            'canonical' => $this->app->baseUrl($path),
            'image' => $settings['hero_media']['public_url'] ?? null,
        ];
    }

    public function seoForEntry(array $entry, array $settings, string $locale = 'pl'): array
    {
        $image = $entry['media'][0]['public_url'] ?? ($settings['hero_media']['public_url'] ?? null);

        return [
            'title' => $entry['seo_title'] ?: $entry['title'],
            'description' => $entry['meta_description'] ?: ($entry['summary'] ?: $settings['translation']['default_meta_description']),
            'og_title' => $entry['og_title'] ?: ($entry['seo_title'] ?: $entry['title']),
            'og_description' => $entry['og_description'] ?: ($entry['meta_description'] ?: ($entry['summary'] ?: $settings['translation']['default_meta_description'])),
            'canonical' => $this->app->baseUrl($this->relativeUrl($entry, $locale)),
            'image' => $image ? $this->app->baseUrl($image) : null,
        ];
    }

    public function sanitizeRichText(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', 'href="#"', $html) ?? $html;
        $html = preg_replace('/src\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', 'src=""', $html) ?? $html;

        return strip_tags(
            $html,
            '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote><figure><figcaption><img>'
        );
    }

    public function sanitizeFooterBarHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', 'href="#"', $html) ?? $html;

        return strip_tags($html, '<a><br><strong><em><span>');
    }

    public function sanitizeLinkHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', 'href="#"', $html) ?? $html;

        return strip_tags($html, '<a><span><strong><em><br><i>');
    }

    public function normalizeSlug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(
            ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'],
            ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'],
            $value
        );
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? $value;

        return trim($value, '-');
    }

    public function statusOptions(): array
    {
        return ['draft', 'published', 'archived'];
    }

    public function contentTypeOptions(): array
    {
        return ['PAGE', 'EXHIBITION', 'EVENT', 'PROJECT', 'COLLECTION', 'GALLERY', 'EMPLOYEE'];
    }

    public function sectionOptions(): array
    {
        return ['MUZEUM', 'PROGRAM', 'ART_BOOK', 'WARSZTAT', 'EDUKACJA', 'SHOP', 'VISIT', 'CONTACT'];
    }

    public function fontPresets(): array
    {
        $configured = (array) $this->app->config('theme.font_presets', []);

        if ($configured !== []) {
            return $configured;
        }

        return [
            'editorial-sans' => [
                'label' => 'Editorial Sans',
                'stack' => '"Avenir Next", "Gill Sans", "Trebuchet MS", sans-serif',
            ],
            'library-serif' => [
                'label' => 'Library Serif',
                'stack' => '"Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif',
            ],
            'technical-sans' => [
                'label' => 'Technical Sans',
                'stack' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
            ],
            'reading-serif' => [
                'label' => 'Reading Serif',
                'stack' => 'Georgia, "Times New Roman", serif',
            ],
            'monospace-editorial' => [
                'label' => 'Monospace Editorial',
                'stack' => '"IBM Plex Mono", "Courier New", Courier, monospace',
            ],
        ];
    }

    public function fontPresetStack(?string $preset): string
    {
        $presets = $this->fontPresets();
        $default = $presets['editorial-sans']['stack']
            ?? '"Avenir Next", "Gill Sans", "Trebuchet MS", sans-serif';

        return $presets[(string) $preset]['stack'] ?? $default;
    }

    public function fontPresetExists(?string $preset): bool
    {
        return array_key_exists((string) $preset, $this->fontPresets());
    }

    public function fontFaceFamily(array $asset): string
    {
        return 'MKAFont' . (int) ($asset['id'] ?? 0);
    }

    public function fontFaceFormat(array $asset): ?string
    {
        $path = strtolower((string) ($asset['disk_path'] ?? $asset['original_name'] ?? ''));
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return match ($extension) {
            'woff2' => 'woff2',
            'woff' => 'woff',
            'ttf' => 'truetype',
            'otf' => 'opentype',
            default => null,
        };
    }
}

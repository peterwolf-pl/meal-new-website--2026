<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\App;

final class Navigation
{
    public function __construct(private readonly ?App $app = null)
    {
    }

    public function groups(string $locale = 'pl'): array
    {
        $databaseGroups = $this->groupsFromDatabase($locale);
        if ($databaseGroups !== []) {
            return $databaseGroups;
        }

        return $this->defaultGroups($locale);
    }

    public function defaultGroups(string $locale = 'pl'): array
    {
        $root = '/' . $locale;

        return [
            [
                'key' => 'MUZEUM',
                'label' => 'MUZEUM',
                'href' => $root . '/muzeum/misja',
                'children' => [
                    ['slug' => 'misja', 'label' => 'misja', 'title' => 'Misja', 'href' => $root . '/muzeum/misja', 'kind' => 'page', 'section_key' => 'MUZEUM'],
                    ['slug' => 'historia', 'label' => 'historia', 'title' => 'Historia', 'href' => $root . '/muzeum/historia', 'kind' => 'page', 'section_key' => 'MUZEUM'],
                    ['slug' => 'wystawa-stala', 'label' => 'wystawa stała', 'title' => 'Wystawa stała', 'href' => $root . '/muzeum/wystawa-stala', 'kind' => 'page', 'section_key' => 'MUZEUM'],
                    ['slug' => 'zespol', 'label' => 'zespół', 'title' => 'Zespół', 'href' => $root . '/muzeum/zespol', 'kind' => 'page', 'section_key' => 'MUZEUM'],
                    ['slug' => 'rada', 'label' => 'rada', 'title' => 'Rada', 'href' => $root . '/muzeum/rada', 'kind' => 'page', 'section_key' => 'MUZEUM'],
                ],
            ],
            [
                'key' => 'PROGRAM',
                'label' => 'PROGRAM',
                'href' => $root . '/program/wystawy',
                'children' => [
                    ['slug' => 'wystawy', 'label' => 'wystawy', 'title' => 'Wystawy', 'href' => $root . '/program/wystawy', 'kind' => 'listing', 'section_key' => 'PROGRAM', 'content_type' => 'EXHIBITION'],
                    ['slug' => 'wydarzenia', 'label' => 'wydarzenia', 'title' => 'Wydarzenia', 'href' => $root . '/program/wydarzenia', 'kind' => 'listing', 'section_key' => 'PROGRAM', 'content_type' => 'EVENT'],
                    ['slug' => 'projekty', 'label' => 'projekty', 'title' => 'Projekty', 'href' => $root . '/program/projekty', 'kind' => 'listing', 'section_key' => 'PROGRAM', 'content_type' => 'PROJECT'],
                ],
            ],
            [
                'key' => 'ART_BOOK',
                'label' => 'KSIĄŻKA ARTYSTYCZNA',
                'href' => $root . '/ksiazka-artystyczna',
                'children' => [
                    ['slug' => 'ksiazka-artystyczna', 'label' => 'czym jest Książka artystyczna', 'title' => 'Czym jest Książka artystyczna', 'href' => $root . '/ksiazka-artystyczna', 'kind' => 'page', 'section_key' => 'ART_BOOK'],
                    ['slug' => 'kolekcja', 'label' => 'kolekcja MKA i CDA', 'title' => 'Kolekcja MKA i CDA', 'href' => $root . '/ksiazka-artystyczna/kolekcja', 'kind' => 'listing', 'section_key' => 'ART_BOOK', 'content_type' => 'COLLECTION'],
                    ['slug' => 'galeria', 'label' => 'galeria', 'title' => 'Galeria', 'href' => $root . '/ksiazka-artystyczna/galeria', 'kind' => 'listing', 'section_key' => 'ART_BOOK', 'content_type' => 'GALLERY'],
                ],
            ],
            [
                'key' => 'WARSZTAT',
                'label' => 'WARSZTAT',
                'href' => $root . '/warsztat/drukarnia',
                'children' => [
                    ['slug' => 'drukarnia', 'label' => 'drukarnia', 'title' => 'Drukarnia', 'href' => $root . '/warsztat/drukarnia', 'kind' => 'page', 'section_key' => 'WARSZTAT'],
                    ['slug' => 'zecernia', 'label' => 'zecernia', 'title' => 'Zecernia', 'href' => $root . '/warsztat/zecernia', 'kind' => 'page', 'section_key' => 'WARSZTAT'],
                    ['slug' => 'odlewnia-czcionek', 'label' => 'odlewnia czcionek', 'title' => 'Odlewnia czcionek', 'href' => $root . '/warsztat/odlewnia-czcionek', 'kind' => 'page', 'section_key' => 'WARSZTAT'],
                    ['slug' => 'introligatornia', 'label' => 'introligatornia', 'title' => 'Introligatornia', 'href' => $root . '/warsztat/introligatornia', 'kind' => 'page', 'section_key' => 'WARSZTAT'],
                    ['slug' => 'zaplecze', 'label' => 'zaplecze', 'title' => 'Zaplecze', 'href' => $root . '/warsztat/zaplecze', 'kind' => 'page', 'section_key' => 'WARSZTAT'],
                ],
            ],
            [
                'key' => 'EDUKACJA',
                'label' => 'EDUKACJA',
                'href' => $root . '/edukacja/warsztaty',
                'children' => [
                    ['slug' => 'warsztaty', 'label' => 'warsztaty', 'title' => 'Warsztaty', 'href' => $root . '/edukacja/warsztaty', 'kind' => 'page', 'section_key' => 'EDUKACJA'],
                    ['slug' => 'lekcje-muzealne', 'label' => 'lekcje muzealne', 'title' => 'Lekcje muzealne', 'href' => $root . '/edukacja/lekcje-muzealne', 'kind' => 'page', 'section_key' => 'EDUKACJA'],
                    ['slug' => 'wycieczki', 'label' => 'wycieczki', 'title' => 'Wycieczki', 'href' => $root . '/edukacja/wycieczki', 'kind' => 'page', 'section_key' => 'EDUKACJA'],
                    ['slug' => 'spotkania-z-artysta', 'label' => 'spotkania z artystą', 'title' => 'Spotkania z artystą', 'href' => $root . '/edukacja/spotkania-z-artysta', 'kind' => 'page', 'section_key' => 'EDUKACJA'],
                    ['slug' => 'dla-szkol-artystycznych', 'label' => 'lekcje i warsztaty dla szkół artystycznych', 'title' => 'Lekcje i warsztaty dla szkół artystycznych', 'href' => $root . '/edukacja/dla-szkol-artystycznych', 'kind' => 'page', 'section_key' => 'EDUKACJA'],
                ],
            ],
            [
                'key' => 'SHOP',
                'label' => 'SKLEP',
                'href' => $root . '/sklep',
                'children' => [
                    ['slug' => 'sklep', 'label' => 'sklep', 'title' => 'Sklep', 'href' => $root . '/sklep', 'kind' => 'page', 'section_key' => 'SHOP'],
                ],
            ],
            [
                'key' => 'VISIT',
                'label' => 'WIZYTA',
                'href' => $root . '/wizyta',
                'children' => [
                    ['slug' => 'wizyta', 'label' => 'wizyta', 'title' => 'Wizyta', 'href' => $root . '/wizyta', 'kind' => 'page', 'section_key' => 'VISIT'],
                ],
            ],
            [
                'key' => 'CONTACT',
                'label' => 'KONTAKT',
                'href' => $root . '/kontakt',
                'children' => [
                    ['slug' => 'kontakt', 'label' => 'kontakt', 'title' => 'Kontakt', 'href' => $root . '/kontakt', 'kind' => 'page', 'section_key' => 'CONTACT'],
                ],
            ],
        ];
    }

    public function sectionLabel(string $sectionKey): string
    {
        return match ($sectionKey) {
            'MUZEUM' => 'Muzeum',
            'PROGRAM' => 'Program',
            'ART_BOOK' => 'Książka artystyczna',
            'WARSZTAT' => 'Warsztat',
            'EDUKACJA' => 'Edukacja',
            'SHOP' => 'Sklep',
            'VISIT' => 'Wizyta',
            'CONTACT' => 'Kontakt',
            default => 'Muzeum Książki Artystycznej',
        };
    }

    public function typeLabel(string $contentType): string
    {
        return match ($contentType) {
            'PAGE' => 'Strona',
            'EXHIBITION' => 'Wystawa',
            'EVENT' => 'Wydarzenie',
            'PROJECT' => 'Projekt',
            'COLLECTION' => 'Obiekt kolekcji',
            'GALLERY' => 'Obiekt galerii',
            'EMPLOYEE' => 'Pracownik',
            default => $contentType,
        };
    }

    private function groupsFromDatabase(string $locale): array
    {
        if ($this->app === null) {
            return [];
        }

        $items = $this->app->navigationRepository()->all($locale);
        if ($items === []) {
            return [];
        }

        $byParent = [];
        foreach ($items as $item) {
            if (empty($item['is_visible'])) {
                continue;
            }

            $byParent[$item['parent_id'] ?? 0][] = $item;
        }

        $groups = [];
        foreach ($byParent[0] ?? [] as $group) {
            $children = [];
            foreach ($byParent[$group['id']] ?? [] as $child) {
                $children[] = $this->mapItem($child, $locale);
            }

            if ($children === []) {
                $children[] = $this->mapItem($group, $locale);
            }

            $groupKey = (string) ($group['section_key'] ?: strtoupper((string) ($group['slug'] ?: $group['label'])));
            $groups[] = [
                'key' => $groupKey,
                'label' => (string) $group['label'],
                'href' => (string) ($group['href'] ?: ($children[0]['href'] ?? ('/' . $locale))),
                'children' => $children,
            ];
        }

        return $groups;
    }

    private function mapItem(array $item, string $locale): array
    {
        $sectionKey = (string) ($item['section_key'] ?? '');
        $slug = (string) ($item['slug'] ?? '');
        $kind = (string) ($item['item_kind'] ?? 'link');
        $contentType = (string) ($item['content_type'] ?? '');
        $href = (string) ($item['href'] ?? '');

        if ($href === '') {
            $href = $this->resolveHref($locale, $sectionKey, $slug, $kind, $contentType);
        }

        return [
            'id' => (int) ($item['id'] ?? 0),
            'slug' => $slug,
            'label' => (string) ($item['label'] ?? ''),
            'title' => (string) ($item['title'] ?: ($item['label'] ?? '')),
            'href' => $href,
            'kind' => $kind,
            'section_key' => $sectionKey,
            'content_type' => $contentType !== '' ? $contentType : null,
        ];
    }

    private function resolveHref(string $locale, string $sectionKey, string $slug, string $kind, string $contentType): string
    {
        $root = '/' . $locale;

        if ($kind === 'listing') {
            return match ($contentType) {
                'EXHIBITION' => $root . '/program/wystawy',
                'EVENT' => $root . '/program/wydarzenia',
                'PROJECT' => $root . '/program/projekty',
                'COLLECTION' => $root . '/ksiazka-artystyczna/kolekcja',
                'GALLERY' => $root . '/ksiazka-artystyczna/galeria',
                default => $root,
            };
        }

        return match ($sectionKey) {
            'MUZEUM' => $root . '/muzeum/' . $slug,
            'WARSZTAT' => $root . '/warsztat/' . $slug,
            'EDUKACJA' => $root . '/edukacja/' . $slug,
            'SHOP' => $root . '/sklep',
            'VISIT' => $root . '/wizyta',
            'CONTACT' => $root . '/kontakt',
            'ART_BOOK' => match ($slug) {
                'kolekcja' => $root . '/ksiazka-artystyczna/kolekcja',
                'galeria' => $root . '/ksiazka-artystyczna/galeria',
                default => $root . '/ksiazka-artystyczna',
            },
            'PROGRAM' => $root . '/program/' . $slug,
            default => $root,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\App;
use PDO;

final class Seeder
{
    public function __construct(private readonly App $app)
    {
    }

    public function run(): string
    {
        if ($this->app->userRepository()->count() > 0 || $this->app->settingRepository()->getLocalized('pl')) {
            return 'Seed skipped: database already contains data.';
        }

        $this->seedMediaAndSettings();
        $this->seedContent();
        $this->seedUser();

        return 'Seed completed.';
    }

    private function seedMediaAndSettings(): void
    {
        $media = [
            [
                'kind' => 'image',
                'disk_path' => null,
                'original_name' => 'hero-unsplash.jpg',
                'external_url' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=1600&q=80',
                'mime_type' => 'image/jpeg',
                'is_decorative' => 0,
                'translations' => [
                    'pl' => [
                        'title' => 'Główna ekspozycja',
                        'alt_text' => 'Stosy książek i wydawnictw artystycznych na stole ekspozycyjnym.',
                        'caption' => 'Widok na główną ekspozycję muzeum.',
                    ],
                    'en' => [],
                ],
            ],
            [
                'kind' => 'image',
                'disk_path' => null,
                'original_name' => 'workshop-unsplash.jpg',
                'external_url' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=1400&q=80',
                'mime_type' => 'image/jpeg',
                'is_decorative' => 0,
                'translations' => [
                    'pl' => [
                        'title' => 'Wnętrze pracowni',
                        'alt_text' => 'Wnętrze pracowni z regałami, prasami i materiałami drukarskimi.',
                        'caption' => 'Pracownia muzeum otwarta dla publiczności.',
                    ],
                    'en' => [],
                ],
            ],
            [
                'kind' => 'pdf',
                'disk_path' => null,
                'original_name' => 'folder-wystawy.pdf',
                'external_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'mime_type' => 'application/pdf',
                'is_decorative' => 0,
                'translations' => [
                    'pl' => [
                        'title' => 'Folder wystawy',
                        'alt_text' => '',
                        'caption' => 'Przykładowy folder w formacie PDF.',
                    ],
                    'en' => [],
                ],
            ],
            [
                'kind' => 'video',
                'disk_path' => null,
                'original_name' => 'spacer-po-kolekcji',
                'external_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'mime_type' => 'text/uri-list',
                'is_decorative' => 0,
                'translations' => [
                    'pl' => [
                        'title' => 'Spacer po kolekcji',
                        'alt_text' => '',
                        'caption' => 'Krótki spacer po kolekcji książki artystycznej.',
                    ],
                    'en' => [],
                ],
            ],
        ];

        $mediaIds = [];
        foreach ($media as $item) {
            $mediaIds[] = $this->app->mediaRepository()->save($item);
        }

        $this->app->settingRepository()->save([
            'contact_email' => 'kontakt@mka.local',
            'phone' => '+48 22 123 45 67',
            'street_address' => 'ul. Przykładowa 12',
            'postal_code' => '00-001',
            'city' => 'Warszawa',
            'map_url' => 'https://maps.google.com/?q=Warszawa',
            'facebook_url' => '',
            'instagram_url' => 'https://instagram.com',
            'youtube_url' => '',
            'hero_media_id' => $mediaIds[0],
            'translations' => [
                'pl' => [
                    'museum_name' => 'Muzeum Książki Artystycznej',
                    'organization_description' => 'Muzeum poświęcone książce artystycznej, drukowi, typografii i praktykom wydawniczym.',
                    'opening_hours' => 'wt.-nd. 11:00-19:00',
                    'homepage_title' => 'Muzeum książki artystycznej, druku i warsztatu wydawniczego.',
                    'homepage_lead' => 'Nowe muzeum poświęcone obiegowi książki artystycznej, kolekcjom, wystawom i praktyce druku.',
                    'homepage_intro' => '<p>Muzeum Książki Artystycznej łączy historię druku, współczesne praktyki wydawnicze i żywy warsztat. Publiczność może tu oglądać kolekcję, uczestniczyć w wydarzeniach, spotykać twórców i zobaczyć od środka proces powstawania książki.</p>',
                    'visit_note' => 'Wizytę najlepiej planować z wyprzedzeniem w przypadku grup szkolnych i warsztatów.',
                    'default_seo_title' => 'Muzeum Książki Artystycznej',
                    'default_meta_description' => 'Wystawy, wydarzenia, kolekcja i warsztat wokół książki artystycznej.',
                    'default_og_title' => 'Muzeum Książki Artystycznej',
                    'default_og_description' => 'Muzeum poświęcone książce artystycznej, drukowi i edukacji.',
                ],
                'en' => [],
            ],
        ]);
    }

    private function seedContent(): void
    {
        $content = [
            $this->page('MUZEUM', 'misja', 'Misja', 'Misją muzeum jest łączenie historii druku, kolekcji i współczesnych praktyk artystycznych.'),
            $this->page('MUZEUM', 'historia', 'Historia', 'Historia miejsca, zbiorów i warsztatów rozwija się od powojennych pracowni do współczesnego muzeum.'),
            $this->page('MUZEUM', 'wystawa-stala', 'Wystawa stała', 'Wystawa stała prowadzi przez historię książki artystycznej, technik drukarskich i praktyk wydawniczych.'),
            $this->page('MUZEUM', 'zespol', 'Zespół', 'Zespół kuratorski, edukacyjny i techniczny pracuje wspólnie nad programem muzeum i opieką nad kolekcją.'),
            $this->page('MUZEUM', 'rada', 'Rada', 'Rada programowa wspiera rozwój instytucji i pomaga wyznaczać kierunki rozwoju programu.'),
            $this->page('PROGRAM', 'wystawy', 'Wystawy', 'Program wystaw muzeum obejmuje pokazy problemowe, ekspozycje historyczne i projekty współczesne.'),
            $this->page('PROGRAM', 'wydarzenia', 'Wydarzenia', 'Kalendarium spotkań, oprowadzań, premier i działań performatywnych.'),
            $this->page('PROGRAM', 'projekty', 'Projekty', 'Długofalowe projekty badawcze, dokumentacyjne i wystawiennicze wokół książki artystycznej.'),
            $this->page('ART_BOOK', 'ksiazka-artystyczna', 'Czym jest Książka artystyczna', 'Książka artystyczna traktuje książkę jako autonomiczne medium twórcze, materialne i narracyjne.'),
            $this->page('ART_BOOK', 'kolekcja', 'Kolekcja MKA i CDA', 'Kolekcja łączy obiekty historyczne i współczesne, druki eksperymentalne oraz unikatowe wydawnictwa.'),
            $this->page('ART_BOOK', 'galeria', 'Galeria', 'Galeria pokazuje wybrane realizacje, dokumentację procesu twórczego i materiały warsztatowe.'),
            $this->page('WARSZTAT', 'drukarnia', 'Drukarnia', 'Drukarnia łączy maszyny historyczne i współczesne techniki drukarskie.'),
            $this->page('WARSZTAT', 'zecernia', 'Zecernia', 'Zecernia pokazuje proces przygotowania tekstu do druku, od ręcznego składu po rekonstrukcje cyfrowe.'),
            $this->page('WARSZTAT', 'odlewnia-czcionek', 'Odlewnia czcionek', 'Odlewnia prezentuje produkcję, konserwację i obieg czcionek.'),
            $this->page('WARSZTAT', 'introligatornia', 'Introligatornia', 'Introligatornia to miejsce pracy z oprawą, papierem i materialnością książki.'),
            $this->page('WARSZTAT', 'zaplecze', 'Zaplecze', 'Zaplecze ujawnia codzienną logistykę pracy muzeum, magazynów i archiwum.'),
            $this->page('EDUKACJA', 'warsztaty', 'Warsztaty', 'Oferta warsztatowa muzeum obejmuje zajęcia dla dzieci, młodzieży, studentów i dorosłych.'),
            $this->page('EDUKACJA', 'lekcje-muzealne', 'Lekcje muzealne', 'Lekcje muzealne łączą teorię książki artystycznej z praktyką typografii i druku.'),
            $this->page('EDUKACJA', 'wycieczki', 'Wycieczki', 'Zwiedzanie z przewodnikiem prowadzi przez ekspozycję stałą, kolekcję i warsztat.'),
            $this->page('EDUKACJA', 'spotkania-z-artysta', 'Spotkania z artystą', 'Spotkania z artystami przybliżają współczesne strategie pracy z książką jako medium.'),
            $this->page('EDUKACJA', 'dla-szkol-artystycznych', 'Lekcje i warsztaty dla szkół artystycznych', 'Rozbudowana ścieżka dla szkół artystycznych obejmuje zajęcia studyjne i warsztatowe.'),
            $this->page('SHOP', 'sklep', 'Sklep', 'Na start sklep działa jako strona informacyjna z publikacjami i wydawnictwami muzeum.'),
            $this->page('VISIT', 'wizyta', 'Wizyta', 'Wszystkie informacje praktyczne: godziny otwarcia, dojazd, bilety i dostępność.'),
            $this->page('CONTACT', 'kontakt', 'Kontakt', 'Dane teleadresowe muzeum, kontakt do zespołu i informacje organizacyjne.'),
            $this->event('EXHIBITION', 'druk-w-ruchu', 'Druk w ruchu', 'Wystawa o eksperymentalnych formach druku i ruchu obrazu.', true, '-2 days', '+28 days', 'Warszawa, sala główna', [1, 3]),
            $this->event('EVENT', 'otwarta-pracownia', 'Otwarta pracownia', 'Spotkanie z drukarzami, introligatorami i kuratorami kolekcji.', true, '+5 days', '+5 days +3 hours', 'Warszawa, warsztat', [2, 4]),
            $this->project('archiwum-ruchome', 'Archiwum ruchome', 'Projekt badawczy o obiegu książki artystycznej i jej dokumentacji.', [1]),
            $this->collection('atlas-ciszy', 'Atlas ciszy', 'Ręcznie oprawiana książka artystyczna, łącząca papier ręcznie czerpany z interwencją typograficzną.', 'MKA', 'Anna Nowak', '2024', true, [1, 3]),
            $this->collection('miasto-zecerskie', 'Miasto zecerskie', 'Eksperymentalny obiekt wydawniczy inspirowany miejskim składem typograficznym.', 'CDA', 'Jan Kowalski', '2022', false, [2, 4]),
            $this->gallery('pracownia-w-ruchu', 'Pracownia w ruchu', 'Dokumentacja procesu twórczego, pracy w drukarni i prób materiałowych.', [2, 4]),
        ];

        foreach ($content as $entry) {
            $this->app->contentRepository()->save($entry);
        }
    }

    private function seedUser(): void
    {
        $this->app->userRepository()->create(
            'admin@mka.local',
            password_hash('Admin!123', PASSWORD_DEFAULT),
            'Administrator'
        );
    }

    private function page(string $section, string $slug, string $title, string $paragraph): array
    {
        return [
            'content_type' => 'PAGE',
            'section_key' => $section,
            'status' => 'published',
            'sort_order' => 10,
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'event_start' => '',
            'event_end' => '',
            'event_location' => '',
            'registration_url' => '',
            'collection_group' => '',
            'creator_name' => '',
            'item_year' => '',
            'media_ids' => [],
            'translations' => [
                'pl' => [
                    'slug' => $slug,
                    'title' => $title,
                    'summary' => $paragraph,
                    'body' => '<p>' . $paragraph . '</p><p>Miejsce, program i kolekcja budowane są tak, by widzowie mogli doświadczyć książki nie tylko jako obiektu, ale też jako procesu, gestu i sytuacji wystawienniczej.</p>',
                    'seo_title' => $title . ' | Muzeum Książki Artystycznej',
                    'meta_description' => $paragraph,
                    'og_title' => $title . ' | Muzeum Książki Artystycznej',
                    'og_description' => $paragraph,
                ],
                'en' => [],
            ],
        ];
    }

    private function event(
        string $type,
        string $slug,
        string $title,
        string $summary,
        bool $featured,
        string $startModifier,
        string $endModifier,
        string $location,
        array $mediaIds
    ): array {
        return [
            'content_type' => $type,
            'section_key' => 'PROGRAM',
            'status' => 'published',
            'sort_order' => 20,
            'featured' => $featured ? 1 : 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'event_start' => date('Y-m-d H:i:s', strtotime($startModifier)),
            'event_end' => date('Y-m-d H:i:s', strtotime($endModifier)),
            'event_location' => $location,
            'registration_url' => '',
            'collection_group' => '',
            'creator_name' => '',
            'item_year' => '',
            'media_ids' => $mediaIds,
            'translations' => [
                'pl' => [
                    'slug' => $slug,
                    'title' => $title,
                    'summary' => $summary,
                    'body' => '<p>' . $summary . '</p><p>Wydarzenie łączy prezentację obiektów z komentarzem kuratorskim i pokazem pracy warsztatowej.</p>',
                    'seo_title' => $title . ' | Program MKA',
                    'meta_description' => $summary,
                    'og_title' => $title . ' | Program MKA',
                    'og_description' => $summary,
                ],
                'en' => [],
            ],
        ];
    }

    private function project(string $slug, string $title, string $summary, array $mediaIds): array
    {
        return [
            'content_type' => 'PROJECT',
            'section_key' => 'PROGRAM',
            'status' => 'published',
            'sort_order' => 30,
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'event_start' => '',
            'event_end' => '',
            'event_location' => '',
            'registration_url' => '',
            'collection_group' => '',
            'creator_name' => '',
            'item_year' => '',
            'media_ids' => $mediaIds,
            'translations' => [
                'pl' => [
                    'slug' => $slug,
                    'title' => $title,
                    'summary' => $summary,
                    'body' => '<p>' . $summary . '</p><p>Projekt łączy badanie, dokumentację i działania publiczne związane z obiegiem książki artystycznej.</p>',
                    'seo_title' => $title . ' | Projekty MKA',
                    'meta_description' => $summary,
                    'og_title' => $title . ' | Projekty MKA',
                    'og_description' => $summary,
                ],
                'en' => [],
            ],
        ];
    }

    private function collection(
        string $slug,
        string $title,
        string $summary,
        string $group,
        string $creator,
        string $year,
        bool $featured,
        array $mediaIds
    ): array {
        return [
            'content_type' => 'COLLECTION',
            'section_key' => 'ART_BOOK',
            'status' => 'published',
            'sort_order' => 10,
            'featured' => $featured ? 1 : 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'event_start' => '',
            'event_end' => '',
            'event_location' => '',
            'registration_url' => '',
            'collection_group' => $group,
            'creator_name' => $creator,
            'item_year' => $year,
            'media_ids' => $mediaIds,
            'translations' => [
                'pl' => [
                    'slug' => $slug,
                    'title' => $title,
                    'summary' => $summary,
                    'body' => '<p>' . $summary . '</p><p>Obiekt prezentowany jest w kontekście praktyk wydawniczych, typografii i eksperymentu formalnego.</p>',
                    'seo_title' => $title . ' | Kolekcja MKA',
                    'meta_description' => $summary,
                    'og_title' => $title . ' | Kolekcja MKA',
                    'og_description' => $summary,
                ],
                'en' => [],
            ],
        ];
    }

    private function gallery(string $slug, string $title, string $summary, array $mediaIds): array
    {
        return [
            'content_type' => 'GALLERY',
            'section_key' => 'ART_BOOK',
            'status' => 'published',
            'sort_order' => 20,
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'event_start' => '',
            'event_end' => '',
            'event_location' => '',
            'registration_url' => '',
            'collection_group' => '',
            'creator_name' => '',
            'item_year' => '',
            'media_ids' => $mediaIds,
            'translations' => [
                'pl' => [
                    'slug' => $slug,
                    'title' => $title,
                    'summary' => $summary,
                    'body' => '<p>' . $summary . '</p><p>Materiał pokazuje kontekst pracy z matrycą, papierem, składem i montażem ekspozycji.</p>',
                    'seo_title' => $title . ' | Galeria MKA',
                    'meta_description' => $summary,
                    'og_title' => $title . ' | Galeria MKA',
                    'og_description' => $summary,
                ],
                'en' => [],
            ],
        ];
    }
}

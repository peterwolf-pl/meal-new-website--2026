<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\App;
use App\Core\Database;
use PDO;

final class SettingRepository
{
    private ?array $settingsColumns = null;

    public function __construct(
        private readonly Database $database,
        private readonly App $app
    ) {
    }

    public function getLocalized(string $locale = 'pl'): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT * FROM settings ORDER BY id ASC LIMIT 1'
        );
        $statement->execute();
        $settings = $statement->fetch();

        if (!$settings) {
            return null;
        }

        $settings += $this->fontDefaults();
        $settings['translations'] = $this->translations((int) $settings['id']);
        $settings['translation'] = $this->pickTranslation($settings['translations'], $locale);
        $settings['hero_media'] = !empty($settings['hero_media_id'])
            ? $this->app->mediaRepository()->findLocalizedById((int) $settings['hero_media_id'], $locale)
            : null;
        $settings['body_font_media'] = !empty($settings['body_font_media_id'])
            ? $this->app->mediaRepository()->findLocalizedById((int) $settings['body_font_media_id'], $locale)
            : null;
        $settings['heading_font_media'] = !empty($settings['heading_font_media_id'])
            ? $this->app->mediaRepository()->findLocalizedById((int) $settings['heading_font_media_id'], $locale)
            : null;
        $settings['menu_font_media'] = !empty($settings['menu_font_media_id'])
            ? $this->app->mediaRepository()->findLocalizedById((int) $settings['menu_font_media_id'], $locale)
            : null;
        $settings['header_logo_media'] = !empty($settings['header_logo_media_id'])
            ? $this->app->mediaRepository()->findLocalizedById((int) $settings['header_logo_media_id'], $locale)
            : null;

        return $settings;
    }

    public function getForAdmin(): ?array
    {
        $settings = $this->getLocalized('pl');
        if (!$settings) {
            return null;
        }

        $settings['translations'] += [
            'pl' => $this->blankTranslation(),
            'en' => $this->blankTranslation(),
        ];
        $settings += $this->fontDefaults();

        return $settings;
    }

    public function save(array $data): int
    {
        return $this->database->transaction(function (PDO $pdo) use ($data): int {
            $base = [
                'contact_email' => trim((string) $data['contact_email']),
                'phone' => trim((string) $data['phone']),
                'street_address' => trim((string) $data['street_address']),
                'postal_code' => trim((string) $data['postal_code']),
                'city' => trim((string) $data['city']),
                'map_url' => trim((string) ($data['map_url'] ?? '')) ?: null,
                'facebook_url' => trim((string) ($data['facebook_url'] ?? '')) ?: null,
                'instagram_url' => trim((string) ($data['instagram_url'] ?? '')) ?: null,
                'youtube_url' => trim((string) ($data['youtube_url'] ?? '')) ?: null,
                'hero_media_id' => !empty($data['hero_media_id']) ? (int) $data['hero_media_id'] : null,
            ];
            $fontFields = [
                'body_font_preset' => trim((string) ($data['body_font_preset'] ?? 'editorial-sans')) ?: 'editorial-sans',
                'body_font_media_id' => !empty($data['body_font_media_id']) ? (int) $data['body_font_media_id'] : null,
                'body_font_size' => trim((string) ($data['body_font_size'] ?? '1rem')) ?: '1rem',
                'body_font_uppercase' => !empty($data['body_font_uppercase']) ? 1 : 0,
                'body_font_letter_spacing' => trim((string) ($data['body_font_letter_spacing'] ?? '0')) ?: '0',
                'heading_font_preset' => trim((string) ($data['heading_font_preset'] ?? 'editorial-sans')) ?: 'editorial-sans',
                'heading_font_media_id' => !empty($data['heading_font_media_id']) ? (int) $data['heading_font_media_id'] : null,
                'heading_font_size' => trim((string) ($data['heading_font_size'] ?? 'clamp(1.8rem, 3vw, 2.8rem)')) ?: 'clamp(1.8rem, 3vw, 2.8rem)',
                'heading_font_capitalize' => !empty($data['heading_font_capitalize']) ? 1 : 0,
                'heading_font_uppercase' => !empty($data['heading_font_uppercase']) ? 1 : 0,
                'heading_font_letter_spacing' => trim((string) ($data['heading_font_letter_spacing'] ?? '-0.04em')) ?: '-0.04em',
                'menu_font_preset' => trim((string) ($data['menu_font_preset'] ?? 'editorial-sans')) ?: 'editorial-sans',
                'menu_font_media_id' => !empty($data['menu_font_media_id']) ? (int) $data['menu_font_media_id'] : null,
                'menu_font_size' => trim((string) ($data['menu_font_size'] ?? 'clamp(2rem, 4vw, 3.6rem)')) ?: 'clamp(2rem, 4vw, 3.6rem)',
                'menu_line_color' => trim((string) ($data['menu_line_color'] ?? '#cbbfb0')) ?: '#cbbfb0',
                'menu_background_color' => trim((string) ($data['menu_background_color'] ?? '#ffffff')) ?: '#ffffff',
                'menu_submenu_background_color' => trim((string) ($data['menu_submenu_background_color'] ?? '#ffffff')) ?: '#ffffff',
                'menu_active_background_color' => trim((string) ($data['menu_active_background_color'] ?? '#ffffff')) ?: '#ffffff',
                'menu_content_background_color' => trim((string) ($data['menu_content_background_color'] ?? '#ffffff')) ?: '#ffffff',
                'menu_font_capitalize' => !empty($data['menu_font_capitalize']) ? 1 : 0,
                'menu_font_uppercase' => !empty($data['menu_font_uppercase']) ? 1 : 0,
                'menu_font_letter_spacing' => trim((string) ($data['menu_font_letter_spacing'] ?? '-0.04em')) ?: '-0.04em',
                'submenu_font_size' => trim((string) ($data['submenu_font_size'] ?? 'clamp(1.35rem, 2.7vw, 2.3rem)')) ?: 'clamp(1.35rem, 2.7vw, 2.3rem)',
                'submenu_font_capitalize' => !empty($data['submenu_font_capitalize']) ? 1 : 0,
                'submenu_font_uppercase' => !empty($data['submenu_font_uppercase']) ? 1 : 0,
                'submenu_font_letter_spacing' => trim((string) ($data['submenu_font_letter_spacing'] ?? '-0.04em')) ?: '-0.04em',
            ];
            $footerFields = [
                'footer_enabled' => !empty($data['footer_enabled']) ? 1 : 0,
                'footer_bar_enabled' => !empty($data['footer_bar_enabled']) ? 1 : 0,
                'footer_bar_text' => trim((string) ($data['footer_bar_text'] ?? '')) ?: 'Copyright © 2026 peterwolf.pl dla Muzeum Książki Artystycznej w Łodzi All Rights Reserved',
            ];
            $headerFields = [
                'header_logo_media_id' => !empty($data['header_logo_media_id']) ? (int) $data['header_logo_media_id'] : null,
                'header_links_html' => trim((string) ($data['header_links_html'] ?? '')) ?: null,
                'header_show_cms_link' => !empty($data['header_show_cms_link']) ? 1 : 0,
                'header_background_color' => trim((string) ($data['header_background_color'] ?? '#ffffff')) ?: '#ffffff',
                'header_font_color' => trim((string) ($data['header_font_color'] ?? '#181614')) ?: '#181614',
                'header_height' => trim((string) ($data['header_height'] ?? '50px')) ?: '50px',
                'header_logo_padding' => trim((string) ($data['header_logo_padding'] ?? '0')) ?: '0',
                'header_logo_margin' => trim((string) ($data['header_logo_margin'] ?? '0')) ?: '0',
            ];
            $integrationFields = [
                'gtm_container_id' => trim((string) ($data['gtm_container_id'] ?? '')) ?: null,
                'ga4_measurement_id' => trim((string) ($data['ga4_measurement_id'] ?? '')) ?: null,
                'ga4_property_id' => trim((string) ($data['ga4_property_id'] ?? '')) ?: null,
                'search_console_property_url' => trim((string) ($data['search_console_property_url'] ?? '')) ?: null,
                'google_service_account_json_path' => trim((string) ($data['google_service_account_json_path'] ?? '')) ?: null,
            ];
            $oauthFields = [];

            foreach (['google_oauth_refresh_token', 'google_oauth_email', 'google_oauth_connected_at'] as $column) {
                if (array_key_exists($column, $data)) {
                    $oauthFields[$column] = trim((string) ($data[$column] ?? '')) ?: null;
                }
            }
            $availableColumns = $this->settingsColumns();

            foreach ($fontFields as $column => $value) {
                if (in_array($column, $availableColumns, true)) {
                    $base[$column] = $value;
                }
            }

            foreach ($footerFields as $column => $value) {
                if (in_array($column, $availableColumns, true)) {
                    $base[$column] = $value;
                }
            }

            foreach ($headerFields as $column => $value) {
                if (in_array($column, $availableColumns, true)) {
                    $base[$column] = $value;
                }
            }

            foreach ($integrationFields as $column => $value) {
                if (in_array($column, $availableColumns, true)) {
                    $base[$column] = $value;
                }
            }

            foreach ($oauthFields as $column => $value) {
                if (in_array($column, $availableColumns, true)) {
                    $base[$column] = $value;
                }
            }

            $existingId = $pdo->query('SELECT id FROM settings ORDER BY id ASC LIMIT 1')->fetchColumn();

            if ($existingId) {
                $base['id'] = (int) $existingId;
                $assignments = [
                    'contact_email = :contact_email',
                    'phone = :phone',
                    'street_address = :street_address',
                    'postal_code = :postal_code',
                    'city = :city',
                    'map_url = :map_url',
                    'facebook_url = :facebook_url',
                    'instagram_url = :instagram_url',
                    'youtube_url = :youtube_url',
                    'hero_media_id = :hero_media_id',
                ];

                foreach (array_keys($fontFields) as $column) {
                    if (array_key_exists($column, $base)) {
                        $assignments[] = $column . ' = :' . $column;
                    }
                }

                foreach (array_keys($footerFields) as $column) {
                    if (array_key_exists($column, $base)) {
                        $assignments[] = $column . ' = :' . $column;
                    }
                }

                foreach (array_keys($headerFields) as $column) {
                    if (array_key_exists($column, $base)) {
                        $assignments[] = $column . ' = :' . $column;
                    }
                }

                foreach (array_keys($integrationFields) as $column) {
                    if (array_key_exists($column, $base)) {
                        $assignments[] = $column . ' = :' . $column;
                    }
                }

                foreach (array_keys($oauthFields) as $column) {
                    if (array_key_exists($column, $base)) {
                        $assignments[] = $column . ' = :' . $column;
                    }
                }

                $assignments[] = 'updated_at = CURRENT_TIMESTAMP';

                $statement = $pdo->prepare(
                    'UPDATE settings SET ' . implode(', ', $assignments) . ' WHERE id = :id'
                );
                $statement->execute($base);
                $id = (int) $existingId;
            } else {
                $columns = array_keys($base);
                $statement = $pdo->prepare(
                    'INSERT INTO settings (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')'
                );
                $statement->execute($base);
                $id = (int) $pdo->lastInsertId();
            }

            foreach (['pl', 'en'] as $locale) {
                $translation = $data['translations'][$locale] ?? [];
                $payload = [
                    'settings_id' => $id,
                    'locale' => $locale,
                    'museum_name' => trim((string) ($translation['museum_name'] ?? '')),
                    'organization_description' => trim((string) ($translation['organization_description'] ?? '')),
                    'opening_hours' => trim((string) ($translation['opening_hours'] ?? '')),
                    'homepage_title' => trim((string) ($translation['homepage_title'] ?? '')),
                    'homepage_lead' => trim((string) ($translation['homepage_lead'] ?? '')),
                    'homepage_intro' => trim((string) ($translation['homepage_intro'] ?? '')),
                    'visit_note' => trim((string) ($translation['visit_note'] ?? '')) ?: null,
                    'default_seo_title' => trim((string) ($translation['default_seo_title'] ?? '')),
                    'default_meta_description' => trim((string) ($translation['default_meta_description'] ?? '')),
                    'default_og_title' => trim((string) ($translation['default_og_title'] ?? '')),
                    'default_og_description' => trim((string) ($translation['default_og_description'] ?? '')),
                ];

                if (
                    $locale === 'pl'
                    || implode('', array_map(static fn(mixed $value): string => trim((string) $value), $payload)) !== ''
                ) {
                    $exists = $pdo->prepare('SELECT id FROM settings_translations WHERE settings_id = :settings_id AND locale = :locale LIMIT 1');
                    $exists->execute(['settings_id' => $id, 'locale' => $locale]);

                    if ($exists->fetchColumn()) {
                        $update = $pdo->prepare(
                            'UPDATE settings_translations
                            SET museum_name = :museum_name, organization_description = :organization_description, opening_hours = :opening_hours,
                                homepage_title = :homepage_title, homepage_lead = :homepage_lead, homepage_intro = :homepage_intro,
                                visit_note = :visit_note, default_seo_title = :default_seo_title,
                                default_meta_description = :default_meta_description, default_og_title = :default_og_title,
                                default_og_description = :default_og_description
                            WHERE settings_id = :settings_id AND locale = :locale'
                        );
                        $update->execute($payload);
                    } else {
                        $insert = $pdo->prepare(
                            'INSERT INTO settings_translations
                            (settings_id, locale, museum_name, organization_description, opening_hours, homepage_title, homepage_lead, homepage_intro,
                             visit_note, default_seo_title, default_meta_description, default_og_title, default_og_description)
                            VALUES
                            (:settings_id, :locale, :museum_name, :organization_description, :opening_hours, :homepage_title, :homepage_lead, :homepage_intro,
                             :visit_note, :default_seo_title, :default_meta_description, :default_og_title, :default_og_description)'
                        );
                        $insert->execute($payload);
                    }
                } elseif ($locale !== 'pl') {
                    $delete = $pdo->prepare('DELETE FROM settings_translations WHERE settings_id = :settings_id AND locale = :locale');
                    $delete->execute(['settings_id' => $id, 'locale' => $locale]);
                }
            }

            return $id;
        });
    }

    public function supportsThemeFonts(): bool
    {
        $columns = $this->settingsColumns();

        foreach ([
            'body_font_preset',
            'body_font_media_id',
            'body_font_size',
            'body_font_uppercase',
            'body_font_letter_spacing',
            'heading_font_preset',
            'heading_font_media_id',
            'heading_font_size',
            'heading_font_capitalize',
            'heading_font_uppercase',
            'heading_font_letter_spacing',
            'menu_font_preset',
            'menu_font_media_id',
            'menu_font_size',
            'menu_background_color',
            'menu_submenu_background_color',
            'menu_active_background_color',
            'menu_content_background_color',
            'menu_font_capitalize',
            'menu_font_uppercase',
            'menu_font_letter_spacing',
            'submenu_font_size',
            'submenu_font_capitalize',
            'submenu_font_uppercase',
            'submenu_font_letter_spacing',
        ] as $column) {
            if (!in_array($column, $columns, true)) {
                return false;
            }
        }

        return true;
    }

    public function updateGoogleAutoconfig(array $data): bool
    {
        return $this->database->transaction(function (PDO $pdo) use ($data): bool {
            $existingId = $pdo->query('SELECT id FROM settings ORDER BY id ASC LIMIT 1')->fetchColumn();
            if (!$existingId) {
                return false;
            }

            $availableColumns = $this->settingsColumns();
            $updates = [];

            foreach ([
                'gtm_container_id',
                'ga4_measurement_id',
                'ga4_property_id',
                'search_console_property_url',
                'google_service_account_json_path',
                'google_oauth_refresh_token',
                'google_oauth_email',
                'google_oauth_connected_at',
            ] as $column) {
                if (!array_key_exists($column, $data) || !in_array($column, $availableColumns, true)) {
                    continue;
                }

                $updates[$column] = trim((string) ($data[$column] ?? '')) ?: null;
            }

            if ($updates === []) {
                return true;
            }

            $updates['id'] = (int) $existingId;
            $assignments = [];

            foreach (array_keys($updates) as $column) {
                if ($column === 'id') {
                    continue;
                }

                $assignments[] = $column . ' = :' . $column;
            }

            $assignments[] = 'updated_at = CURRENT_TIMESTAMP';

            $statement = $pdo->prepare(
                'UPDATE settings SET ' . implode(', ', $assignments) . ' WHERE id = :id'
            );
            $statement->execute($updates);

            return true;
        });
    }

    public function clearGoogleOauthConnection(): bool
    {
        return $this->updateGoogleAutoconfig([
            'google_oauth_refresh_token' => '',
            'google_oauth_email' => '',
            'google_oauth_connected_at' => '',
        ]);
    }

    private function translations(int $settingsId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT locale, museum_name, organization_description, opening_hours, homepage_title, homepage_lead, homepage_intro,
                    visit_note, default_seo_title, default_meta_description, default_og_title, default_og_description
            FROM settings_translations
            WHERE settings_id = :settings_id'
        );
        $statement->execute(['settings_id' => $settingsId]);

        $translations = [];
        foreach ($statement->fetchAll() ?: [] as $translation) {
            $translations[$translation['locale']] = $translation;
        }

        return $translations;
    }

    private function pickTranslation(array $translations, string $locale): array
    {
        return $translations[$locale] ?? $translations[$this->app->defaultLocale()] ?? $this->blankTranslation();
    }

    private function blankTranslation(): array
    {
        return [
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
        ];
    }

    private function fontDefaults(): array
    {
        return [
            'body_font_preset' => 'editorial-sans',
            'body_font_media_id' => null,
            'body_font_size' => '1rem',
            'body_font_uppercase' => 0,
            'body_font_letter_spacing' => '0',
            'heading_font_preset' => 'editorial-sans',
            'heading_font_media_id' => null,
            'heading_font_size' => 'clamp(1.8rem, 3vw, 2.8rem)',
            'heading_font_capitalize' => 0,
            'heading_font_uppercase' => 0,
            'heading_font_letter_spacing' => '-0.04em',
            'menu_font_preset' => 'editorial-sans',
            'menu_font_media_id' => null,
            'menu_font_size' => 'clamp(2rem, 4vw, 3.6rem)',
            'menu_line_color' => '#cbbfb0',
            'menu_background_color' => '#ffffff',
            'menu_submenu_background_color' => '#ffffff',
            'menu_active_background_color' => '#ffffff',
            'menu_content_background_color' => '#ffffff',
            'menu_font_capitalize' => 0,
            'menu_font_uppercase' => 1,
            'menu_font_letter_spacing' => '-0.04em',
            'submenu_font_size' => 'clamp(1.35rem, 2.7vw, 2.3rem)',
            'submenu_font_capitalize' => 0,
            'submenu_font_uppercase' => 0,
            'submenu_font_letter_spacing' => '-0.04em',
            'footer_enabled' => 1,
            'footer_bar_enabled' => 1,
            'footer_bar_text' => 'Copyright © 2026 peterwolf.pl dla Muzeum Książki Artystycznej w Łodzi All Rights Reserved',
            'header_logo_media_id' => null,
            'header_links_html' => '',
            'header_show_cms_link' => 1,
            'header_background_color' => '#ffffff',
            'header_font_color' => '#181614',
            'header_height' => '50px',
            'header_logo_padding' => '0',
            'header_logo_margin' => '0',
            'gtm_container_id' => '',
            'ga4_measurement_id' => '',
            'ga4_property_id' => '',
            'search_console_property_url' => '',
            'google_service_account_json_path' => '',
            'google_oauth_refresh_token' => '',
            'google_oauth_email' => '',
            'google_oauth_connected_at' => '',
            'body_font_media' => null,
            'heading_font_media' => null,
            'menu_font_media' => null,
            'header_logo_media' => null,
        ];
    }

    private function settingsColumns(): array
    {
        if ($this->settingsColumns !== null) {
            return $this->settingsColumns;
        }

        $this->settingsColumns = $this->database->tableColumns('settings');

        return $this->settingsColumns;
    }
}

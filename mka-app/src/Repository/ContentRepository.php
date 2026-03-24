<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\App;
use App\Core\Database;
use PDO;

final class ContentRepository
{
    private ?array $entryColumns = null;
    private ?array $translationColumns = null;

    public function __construct(
        private readonly Database $database,
        private readonly App $app
    ) {
    }

    public function count(): int
    {
        return (int) $this->database->pdo()->query('SELECT COUNT(*) FROM content_entries')->fetchColumn();
    }

    public function countByStatus(): array
    {
        $rows = $this->database->pdo()->query(
            'SELECT status, COUNT(*) AS aggregate FROM content_entries GROUP BY status'
        )->fetchAll() ?: [];

        $counts = ['draft' => 0, 'published' => 0, 'archived' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['aggregate'];
        }

        return $counts;
    }

    public function latestForDashboard(string $locale = 'pl', int $limit = 6): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            ORDER BY ce.updated_at DESC, ce.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':locale', $locale);
        $statement->bindValue(':fallback', $this->app->defaultLocale());
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function homeFeatured(string $locale = 'pl', int $limit = 4): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.featured = 1
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            ORDER BY ce.sort_order ASC, ce.published_at DESC, ce.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':locale', $locale);
        $statement->bindValue(':fallback', $this->app->defaultLocale());
        $statement->bindValue(':status', 'published');
        $statement->bindValue(':now', date('Y-m-d H:i:s'));
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function currentExhibitions(string $locale = 'pl', int $limit = 3): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.content_type = :content_type
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            AND (ce.event_end IS NULL OR ce.event_end >= :now)
            ORDER BY ce.event_start ASC, ce.sort_order ASC, ce.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':locale', $locale);
        $statement->bindValue(':fallback', $this->app->defaultLocale());
        $statement->bindValue(':content_type', 'EXHIBITION');
        $statement->bindValue(':status', 'published');
        $statement->bindValue(':now', date('Y-m-d H:i:s'));
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function upcomingEvents(string $locale = 'pl', int $limit = 3): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.content_type = :content_type
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            AND (ce.event_start IS NULL OR ce.event_start >= :now)
            ORDER BY ce.event_start ASC, ce.sort_order ASC, ce.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':locale', $locale);
        $statement->bindValue(':fallback', $this->app->defaultLocale());
        $statement->bindValue(':content_type', 'EVENT');
        $statement->bindValue(':status', 'published');
        $statement->bindValue(':now', date('Y-m-d H:i:s'));
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function featuredCollections(string $locale = 'pl', int $limit = 4): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.content_type = :content_type
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            ORDER BY ce.featured DESC, ce.sort_order ASC, ce.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':locale', $locale);
        $statement->bindValue(':fallback', $this->app->defaultLocale());
        $statement->bindValue(':content_type', 'COLLECTION');
        $statement->bindValue(':status', 'published');
        $statement->bindValue(':now', date('Y-m-d H:i:s'));
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function findPage(string $sectionKey, string $slug, string $locale = 'pl'): ?array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.content_type = :content_type
            AND ce.section_key = :section_key
            AND COALESCE(t.slug, tf.slug) = :slug
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            LIMIT 1'
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
            'content_type' => 'PAGE',
            'section_key' => $sectionKey,
            'slug' => $slug,
            'status' => 'published',
            'now' => date('Y-m-d H:i:s'),
        ]);

        $item = $statement->fetch();

        return $item ? $this->attachSingle($item, $locale) : null;
    }

    public function listByType(string $contentType, string $locale = 'pl'): array
    {
        $orderBy = in_array($contentType, ['EXHIBITION', 'EVENT'], true)
            ? 'COALESCE(ce.event_start, ce.published_at, ce.created_at) ASC, ce.sort_order ASC, ce.id DESC'
            : 'ce.sort_order ASC, ce.published_at DESC, ce.id DESC';

        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . "
            AND ce.content_type = :content_type
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            ORDER BY {$orderBy}"
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
            'content_type' => $contentType,
            'status' => 'published',
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function listEmployees(string $locale = 'pl'): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.content_type = :content_type
            AND ce.section_key = :section_key
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            ORDER BY ce.sort_order ASC, ce.id DESC'
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
            'content_type' => 'EMPLOYEE',
            'section_key' => 'MUZEUM',
            'status' => 'published',
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $this->attachMedia($statement->fetchAll() ?: [], $locale);
    }

    public function findDetail(string $contentType, string $slug, string $locale = 'pl'): ?array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.content_type = :content_type
            AND COALESCE(t.slug, tf.slug) = :slug
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            LIMIT 1'
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
            'content_type' => $contentType,
            'slug' => $slug,
            'status' => 'published',
            'now' => date('Y-m-d H:i:s'),
        ]);

        $item = $statement->fetch();

        return $item ? $this->attachSingle($item, $locale) : null;
    }

    public function findAlternatePageSlug(string $sectionKey, string $slug, string $sourceLocale, string $targetLocale): ?string
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT tt.slug
            FROM content_entries ce
            INNER JOIN content_entry_translations ts ON ts.content_entry_id = ce.id AND ts.locale = :source_locale
            INNER JOIN content_entry_translations tt ON tt.content_entry_id = ce.id AND tt.locale = :target_locale
            WHERE ce.content_type = :content_type
              AND ce.section_key = :section_key
              AND ts.slug = :slug
            LIMIT 1'
        );
        $statement->execute([
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'content_type' => 'PAGE',
            'section_key' => $sectionKey,
            'slug' => $slug,
        ]);

        $translatedSlug = $statement->fetchColumn();

        return is_string($translatedSlug) && trim($translatedSlug) !== '' ? trim($translatedSlug) : null;
    }

    public function findLocalizedById(int $id, string $locale = 'pl'): ?array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.id = :id
            LIMIT 1'
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
            'id' => $id,
        ]);

        $item = $statement->fetch();

        return $item ? $this->attachSingle($item, $locale) : null;
    }

    public function allForAdmin(): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT
                ce.*,
                COALESCE(t.title, \'\') AS title,
                COALESCE(t.slug, \'\') AS slug
            FROM content_entries ce
            LEFT JOIN content_entry_translations t ON t.content_entry_id = ce.id AND t.locale = :locale
            ORDER BY ce.section_key ASC, ce.content_type ASC, ce.sort_order ASC, ce.id DESC'
        );
        $statement->execute(['locale' => 'pl']);

        return $statement->fetchAll() ?: [];
    }

    public function findForAdmin(int $id): ?array
    {
        $statement = $this->database->pdo()->prepare('SELECT * FROM content_entries WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $entry = $statement->fetch();

        if (!$entry) {
            return null;
        }

        $translationsStatement = $this->database->pdo()->prepare(
            'SELECT locale, slug, title, summary, body, '
            . ($this->supportsEmployeeProjects() ? 'employee_projects' : 'NULL AS employee_projects')
            . ', ' . ($this->supportsSeoKeywords() ? 'seo_keywords' : 'NULL AS seo_keywords')
            . ', seo_title, meta_description, og_title, og_description
            FROM content_entry_translations
            WHERE content_entry_id = :id'
        );
        $translationsStatement->execute(['id' => $id]);

        $translations = [
            'pl' => $this->blankTranslation(),
            'en' => $this->blankTranslation(),
        ];

        foreach ($translationsStatement->fetchAll() ?: [] as $translation) {
            $translations[$translation['locale']] = [
                'slug' => $translation['slug'],
                'title' => $translation['title'],
                'summary' => $translation['summary'] ?? '',
                'body' => $translation['body'],
                'employee_projects' => $translation['employee_projects'] ?? '',
                'seo_keywords' => $translation['seo_keywords'] ?? '',
                'seo_title' => $translation['seo_title'] ?? '',
                'meta_description' => $translation['meta_description'] ?? '',
                'og_title' => $translation['og_title'] ?? '',
                'og_description' => $translation['og_description'] ?? '',
            ];
        }

        $mediaStatement = $this->database->pdo()->prepare(
            'SELECT media_asset_id FROM content_entry_media WHERE content_entry_id = :id ORDER BY sort_order ASC, media_asset_id ASC'
        );
        $mediaStatement->execute(['id' => $id]);

        $entry['translations'] = $translations;
        $entry['media_ids'] = array_map('intval', $mediaStatement->fetchAll(PDO::FETCH_COLUMN) ?: []);

        return $entry;
    }

    public function save(array $data): int
    {
        return $this->database->transaction(function (PDO $pdo) use ($data): int {
        $base = [
            'content_type' => strtoupper((string) $data['content_type']),
            'section_key' => strtoupper((string) $data['section_key']),
            'status' => strtolower((string) $data['status']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
                'featured' => !empty($data['featured']) ? 1 : 0,
                'published_at' => $this->nullableDate($data['published_at'] ?? null),
                'event_start' => $this->nullableDate($data['event_start'] ?? null),
                'event_end' => $this->nullableDate($data['event_end'] ?? null),
                'event_location' => trim((string) ($data['event_location'] ?? '')) ?: null,
                'registration_url' => trim((string) ($data['registration_url'] ?? '')) ?: null,
                'collection_group' => trim((string) ($data['collection_group'] ?? '')) ?: null,
                'creator_name' => trim((string) ($data['creator_name'] ?? '')) ?: null,
                'item_year' => trim((string) ($data['item_year'] ?? '')) ?: null,
            ];
            $entryColumns = [
                'content_type',
                'section_key',
                'status',
                'sort_order',
                'featured',
                'published_at',
                'event_start',
                'event_end',
                'event_location',
                'registration_url',
                'collection_group',
                'creator_name',
                'item_year',
            ];

            if ($this->supportsContactEmail()) {
                $base['contact_email'] = trim((string) ($data['contact_email'] ?? '')) ?: null;
                $entryColumns[] = 'contact_email';
            }

            if (!empty($data['id'])) {
                $base['id'] = (int) $data['id'];
                $assignments = array_map(
                    static fn(string $column): string => $column . ' = :' . $column,
                    $entryColumns
                );
                $assignments[] = 'updated_at = CURRENT_TIMESTAMP';
                $statement = $pdo->prepare(
                    'UPDATE content_entries
                    SET ' . implode(', ', $assignments) . '
                    WHERE id = :id'
                );
                $statement->execute($base);
                $id = (int) $data['id'];
            } else {
                $insertColumns = $entryColumns;
                $statement = $pdo->prepare(
                    'INSERT INTO content_entries
                    (' . implode(', ', $insertColumns) . ')
                    VALUES
                    (:' . implode(', :', $insertColumns) . ')'
                );
                $statement->execute($base);
                $id = (int) $pdo->lastInsertId();
            }

            foreach (['pl', 'en'] as $locale) {
                $translation = $data['translations'][$locale] ?? [];
                $payload = [
                    'content_entry_id' => $id,
                    'locale' => $locale,
                    'slug' => trim((string) ($translation['slug'] ?? '')),
                    'title' => trim((string) ($translation['title'] ?? '')),
                    'summary' => trim((string) ($translation['summary'] ?? '')) ?: null,
                    'body' => trim((string) ($translation['body'] ?? '')),
                    'employee_projects' => trim((string) ($translation['employee_projects'] ?? '')) ?: null,
                    'seo_keywords' => trim((string) ($translation['seo_keywords'] ?? '')) ?: null,
                    'seo_title' => trim((string) ($translation['seo_title'] ?? '')) ?: null,
                    'meta_description' => trim((string) ($translation['meta_description'] ?? '')) ?: null,
                    'og_title' => trim((string) ($translation['og_title'] ?? '')) ?: null,
                    'og_description' => trim((string) ($translation['og_description'] ?? '')) ?: null,
                ];

                $translationColumns = [
                    'slug',
                    'title',
                    'summary',
                    'body',
                ];

                if ($this->supportsEmployeeProjects()) {
                    $translationColumns[] = 'employee_projects';
                } else {
                    unset($payload['employee_projects']);
                }

                if ($this->supportsSeoKeywords()) {
                    $translationColumns[] = 'seo_keywords';
                } else {
                    unset($payload['seo_keywords']);
                }

                $translationColumns = array_merge($translationColumns, [
                    'seo_title',
                    'meta_description',
                    'og_title',
                    'og_description',
                ]);

                if ($locale === 'pl' || $payload['slug'] !== '' || $payload['title'] !== '' || $payload['body'] !== '') {
                    $exists = $pdo->prepare(
                        'SELECT id FROM content_entry_translations WHERE content_entry_id = :content_entry_id AND locale = :locale LIMIT 1'
                    );
                    $exists->execute(['content_entry_id' => $id, 'locale' => $locale]);

                    if ($exists->fetchColumn()) {
                        $assignments = array_map(
                            static fn(string $column): string => $column . ' = :' . $column,
                            $translationColumns
                        );
                        $update = $pdo->prepare(
                            'UPDATE content_entry_translations
                            SET ' . implode(', ', $assignments) . '
                            WHERE content_entry_id = :content_entry_id AND locale = :locale'
                        );
                        $update->execute($payload);
                    } else {
                        $insertColumns = array_merge(['content_entry_id', 'locale'], $translationColumns);
                        $insert = $pdo->prepare(
                            'INSERT INTO content_entry_translations
                            (' . implode(', ', $insertColumns) . ')
                            VALUES
                            (:' . implode(', :', $insertColumns) . ')'
                        );
                        $insert->execute($payload);
                    }
                } elseif ($locale !== 'pl') {
                    $delete = $pdo->prepare(
                        'DELETE FROM content_entry_translations WHERE content_entry_id = :content_entry_id AND locale = :locale'
                    );
                    $delete->execute(['content_entry_id' => $id, 'locale' => $locale]);
                }
            }

            $deleteMedia = $pdo->prepare('DELETE FROM content_entry_media WHERE content_entry_id = :content_entry_id');
            $deleteMedia->execute(['content_entry_id' => $id]);

            $mediaIds = array_values(array_unique(array_map('intval', $data['media_ids'] ?? [])));
            if ($mediaIds !== []) {
                $insertMedia = $pdo->prepare(
                    'INSERT INTO content_entry_media (content_entry_id, media_asset_id, sort_order)
                    VALUES (:content_entry_id, :media_asset_id, :sort_order)'
                );

                foreach ($mediaIds as $index => $mediaId) {
                    $insertMedia->execute([
                        'content_entry_id' => $id,
                        'media_asset_id' => $mediaId,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $id;
        });
    }

    public function delete(int $id): bool
    {
        $statement = $this->database->pdo()->prepare('DELETE FROM content_entries WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function slugExists(string $locale, string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM content_entry_translations WHERE locale = :locale AND slug = :slug';
        $params = [
            'locale' => $locale,
            'slug' => $slug,
        ];

        if ($excludeId) {
            $sql .= ' AND content_entry_id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $statement = $this->database->pdo()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function publishedForSitemap(string $locale = 'pl'): array
    {
        $statement = $this->database->pdo()->prepare(
            $this->localizedSelect() . '
            AND ce.status = :status
            AND (ce.published_at IS NULL OR ce.published_at <= :now)
            ORDER BY ce.section_key ASC, ce.content_type ASC, ce.sort_order ASC, ce.id ASC'
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
            'status' => 'published',
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $statement->fetchAll() ?: [];
    }

    public function supportsEmployeeProjects(): bool
    {
        return in_array('employee_projects', $this->translationColumns(), true);
    }

    public function supportsSeoKeywords(): bool
    {
        return in_array('seo_keywords', $this->translationColumns(), true);
    }

    public function supportsContactEmail(): bool
    {
        return in_array('contact_email', $this->entryColumns(), true);
    }

    private function localizedSelect(): string
    {
        return 'SELECT
                ce.*,
                COALESCE(t.locale, tf.locale, :fallback) AS active_locale,
                COALESCE(t.slug, tf.slug) AS slug,
                COALESCE(t.title, tf.title) AS title,
                COALESCE(t.summary, tf.summary) AS summary,
                COALESCE(t.body, tf.body) AS body,
                ' . ($this->supportsEmployeeProjects()
                    ? 'COALESCE(t.employee_projects, tf.employee_projects) AS employee_projects,'
                    : 'NULL AS employee_projects,') . '
                ' . ($this->supportsSeoKeywords()
                    ? 'COALESCE(t.seo_keywords, tf.seo_keywords) AS seo_keywords,'
                    : 'NULL AS seo_keywords,') . '
                COALESCE(t.seo_title, tf.seo_title, COALESCE(t.title, tf.title)) AS seo_title,
                COALESCE(t.meta_description, tf.meta_description, COALESCE(t.summary, tf.summary)) AS meta_description,
                COALESCE(t.og_title, tf.og_title, COALESCE(t.seo_title, tf.seo_title, COALESCE(t.title, tf.title))) AS og_title,
                COALESCE(t.og_description, tf.og_description, COALESCE(t.meta_description, tf.meta_description, COALESCE(t.summary, tf.summary))) AS og_description
            FROM content_entries ce
            LEFT JOIN content_entry_translations t ON t.content_entry_id = ce.id AND t.locale = :locale
            LEFT JOIN content_entry_translations tf ON tf.content_entry_id = ce.id AND tf.locale = :fallback
            WHERE 1 = 1';
    }

    private function attachMedia(array $rows, string $locale): array
    {
        if ($rows === []) {
            return [];
        }

        $media = $this->app->mediaRepository()->forContentIds(array_column($rows, 'id'), $locale);

        return array_map(function (array $row) use ($media): array {
            $row['media'] = $media[(int) $row['id']] ?? [];
            return $row;
        }, $rows);
    }

    private function attachSingle(array $row, string $locale): array
    {
        $items = $this->attachMedia([$row], $locale);

        return $items[0];
    }

    private function blankTranslation(): array
    {
        return [
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
        ];
    }

    private function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function entryColumns(): array
    {
        if ($this->entryColumns !== null) {
            return $this->entryColumns;
        }

        $this->entryColumns = $this->database->tableColumns('content_entries');

        return $this->entryColumns;
    }

    private function translationColumns(): array
    {
        if ($this->translationColumns !== null) {
            return $this->translationColumns;
        }

        $this->translationColumns = $this->database->tableColumns('content_entry_translations');

        return $this->translationColumns;
    }
}

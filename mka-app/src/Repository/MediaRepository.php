<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\App;
use App\Core\Database;
use PDO;

final class MediaRepository
{
    public function __construct(
        private readonly Database $database,
        private readonly App $app
    ) {
    }

    public function count(): int
    {
        return (int) $this->database->pdo()->query('SELECT COUNT(*) FROM media_assets')->fetchColumn();
    }

    public function allLocalized(string $locale = 'pl'): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT
                ma.*,
                COALESCE(mt.locale, mt_pl.locale, :fallback) AS active_locale,
                COALESCE(mt.title, mt_pl.title, ma.original_name, ma.kind) AS title,
                COALESCE(mt.alt_text, mt_pl.alt_text) AS alt_text,
                COALESCE(mt.caption, mt_pl.caption) AS caption
            FROM media_assets ma
            LEFT JOIN media_asset_translations mt ON mt.media_asset_id = ma.id AND mt.locale = :locale
            LEFT JOIN media_asset_translations mt_pl ON mt_pl.media_asset_id = ma.id AND mt_pl.locale = :fallback
            ORDER BY ma.id DESC'
        );
        $statement->execute([
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
        ]);

        $items = $statement->fetchAll() ?: [];

        return array_map(fn(array $item): array => $this->decorateLocalized($item), $items);
    }

    public function findLocalizedById(int $id, string $locale = 'pl'): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT
                ma.*,
                COALESCE(mt.locale, mt_pl.locale, :fallback) AS active_locale,
                COALESCE(mt.title, mt_pl.title, ma.original_name, ma.kind) AS title,
                COALESCE(mt.alt_text, mt_pl.alt_text) AS alt_text,
                COALESCE(mt.caption, mt_pl.caption) AS caption
            FROM media_assets ma
            LEFT JOIN media_asset_translations mt ON mt.media_asset_id = ma.id AND mt.locale = :locale
            LEFT JOIN media_asset_translations mt_pl ON mt_pl.media_asset_id = ma.id AND mt_pl.locale = :fallback
            WHERE ma.id = :id
            LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'locale' => $locale,
            'fallback' => $this->app->defaultLocale(),
        ]);

        $item = $statement->fetch();

        return $item ? $this->decorateLocalized($item) : null;
    }

    public function findForAdmin(int $id): ?array
    {
        $statement = $this->database->pdo()->prepare('SELECT * FROM media_assets WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $asset = $statement->fetch();

        if (!$asset) {
            return null;
        }

        $translationsStatement = $this->database->pdo()->prepare(
            'SELECT locale, title, alt_text, caption FROM media_asset_translations WHERE media_asset_id = :id'
        );
        $translationsStatement->execute(['id' => $id]);

        $translations = [
            'pl' => ['title' => '', 'alt_text' => '', 'caption' => ''],
            'en' => ['title' => '', 'alt_text' => '', 'caption' => ''],
        ];

        foreach ($translationsStatement->fetchAll() ?: [] as $translation) {
            $translations[$translation['locale']] = [
                'title' => $translation['title'],
                'alt_text' => $translation['alt_text'] ?? '',
                'caption' => $translation['caption'] ?? '',
            ];
        }

        $asset['translations'] = $translations;
        $asset['public_url'] = $this->publicUrl($asset);

        return $asset;
    }

    public function save(array $data): int
    {
        return $this->database->transaction(function (PDO $pdo) use ($data): int {
            $base = [
                'kind' => strtolower((string) $data['kind']),
                'disk_path' => $data['disk_path'] ?: null,
                'original_name' => $data['original_name'] ?: null,
                'external_url' => $data['external_url'] ?: null,
                'mime_type' => $data['mime_type'] ?: null,
                'is_decorative' => !empty($data['is_decorative']) ? 1 : 0,
            ];

            if (!empty($data['id'])) {
                $base['id'] = (int) $data['id'];
                $statement = $pdo->prepare(
                    'UPDATE media_assets
                    SET kind = :kind, disk_path = :disk_path, original_name = :original_name, external_url = :external_url,
                        mime_type = :mime_type, is_decorative = :is_decorative, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id'
                );
                $statement->execute($base);
                $id = (int) $data['id'];
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO media_assets (kind, disk_path, original_name, external_url, mime_type, is_decorative)
                    VALUES (:kind, :disk_path, :original_name, :external_url, :mime_type, :is_decorative)'
                );
                $statement->execute($base);
                $id = (int) $pdo->lastInsertId();
            }

            foreach (['pl', 'en'] as $locale) {
                $translation = $data['translations'][$locale] ?? [];
                $title = trim((string) ($translation['title'] ?? ''));
                $altText = trim((string) ($translation['alt_text'] ?? ''));
                $caption = trim((string) ($translation['caption'] ?? ''));

                if ($locale === 'pl' || $title !== '' || $altText !== '' || $caption !== '') {
                    $exists = $pdo->prepare(
                        'SELECT id FROM media_asset_translations WHERE media_asset_id = :media_asset_id AND locale = :locale LIMIT 1'
                    );
                    $exists->execute(['media_asset_id' => $id, 'locale' => $locale]);
                    $translationId = $exists->fetchColumn();

                    if ($translationId) {
                        $update = $pdo->prepare(
                            'UPDATE media_asset_translations
                            SET title = :title, alt_text = :alt_text, caption = :caption
                            WHERE media_asset_id = :media_asset_id AND locale = :locale'
                        );
                        $update->execute([
                            'media_asset_id' => $id,
                            'locale' => $locale,
                            'title' => $title !== '' ? $title : ($locale === 'pl' ? ($base['original_name'] ?? 'Medium') : ''),
                            'alt_text' => $altText ?: null,
                            'caption' => $caption ?: null,
                        ]);
                    } else {
                        $insert = $pdo->prepare(
                            'INSERT INTO media_asset_translations (media_asset_id, locale, title, alt_text, caption)
                            VALUES (:media_asset_id, :locale, :title, :alt_text, :caption)'
                        );
                        $insert->execute([
                            'media_asset_id' => $id,
                            'locale' => $locale,
                            'title' => $title !== '' ? $title : ($locale === 'pl' ? ($base['original_name'] ?? 'Medium') : ''),
                            'alt_text' => $altText ?: null,
                            'caption' => $caption ?: null,
                        ]);
                    }
                } elseif ($locale !== 'pl') {
                    $delete = $pdo->prepare('DELETE FROM media_asset_translations WHERE media_asset_id = :id AND locale = :locale');
                    $delete->execute(['id' => $id, 'locale' => $locale]);
                }
            }

            return $id;
        });
    }

    public function delete(int $id): ?array
    {
        $asset = $this->findForAdmin($id);
        if (!$asset) {
            return null;
        }

        $statement = $this->database->pdo()->prepare('DELETE FROM media_assets WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $asset;
    }

    public function forContentIds(array $contentIds, string $locale = 'pl'): array
    {
        $contentIds = array_values(array_unique(array_map('intval', $contentIds)));
        if ($contentIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($contentIds), '?'));
        $sql = sprintf(
            'SELECT
                cem.content_entry_id,
                cem.sort_order,
                ma.*,
                COALESCE(mt.locale, mt_pl.locale, ?) AS active_locale,
                COALESCE(mt.title, mt_pl.title, ma.original_name, ma.kind) AS title,
                COALESCE(mt.alt_text, mt_pl.alt_text) AS alt_text,
                COALESCE(mt.caption, mt_pl.caption) AS caption
            FROM content_entry_media cem
            INNER JOIN media_assets ma ON ma.id = cem.media_asset_id
            LEFT JOIN media_asset_translations mt ON mt.media_asset_id = ma.id AND mt.locale = ?
            LEFT JOIN media_asset_translations mt_pl ON mt_pl.media_asset_id = ma.id AND mt_pl.locale = ?
            WHERE cem.content_entry_id IN (%s)
            ORDER BY cem.content_entry_id ASC, cem.sort_order ASC, ma.id ASC',
            $placeholders
        );

        $params = [$this->app->defaultLocale(), $locale, $this->app->defaultLocale(), ...$contentIds];
        $statement = $this->database->pdo()->prepare($sql);
        $statement->execute($params);

        $grouped = [];

        foreach ($statement->fetchAll() ?: [] as $row) {
            $contentId = (int) $row['content_entry_id'];
            $grouped[$contentId][] = $this->decorateLocalized($row);
        }

        return $grouped;
    }

    public function publicUrl(array $asset): string
    {
        if (!empty($asset['external_url'])) {
            return (string) $asset['external_url'];
        }

        return '/media/' . ltrim((string) $asset['disk_path'], '/');
    }

    private function decorateLocalized(array $item): array
    {
        $item['public_url'] = $this->publicUrl($item);

        return $item;
    }
}

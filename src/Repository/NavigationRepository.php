<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

final class NavigationRepository
{
    private ?bool $navigationTableExists = null;

    public function __construct(private readonly Database $database)
    {
    }

    public function tableExists(): bool
    {
        if ($this->navigationTableExists !== null) {
            return $this->navigationTableExists;
        }

        $this->navigationTableExists = $this->database->tableExists('navigation_items');

        return $this->navigationTableExists;
    }

    public function all(string $locale = 'pl'): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $statement = $this->database->pdo()->prepare(
            'SELECT * FROM navigation_items WHERE locale = :locale ORDER BY parent_id ASC, sort_order ASC, id ASC'
        );
        $statement->execute(['locale' => $locale]);

        return $statement->fetchAll() ?: [];
    }

    public function tree(string $locale = 'pl'): array
    {
        $items = $this->all($locale);
        if ($items === []) {
            return [];
        }

        $byParent = [];
        foreach ($items as $item) {
            $byParent[(int) ($item['parent_id'] ?? 0)][] = $item;
        }

        return $this->buildTree($byParent, 0);
    }

    public function count(string $locale = 'pl'): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $statement = $this->database->pdo()->prepare('SELECT COUNT(*) FROM navigation_items WHERE locale = :locale');
        $statement->execute(['locale' => $locale]);

        return (int) $statement->fetchColumn();
    }

    public function find(int $id): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $statement = $this->database->pdo()->prepare('SELECT * FROM navigation_items WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $item = $statement->fetch();

        return $item ?: null;
    }

    public function save(array $data): int
    {
        $pdo = $this->database->pdo();
        $sortOrder = isset($data['sort_order']) && $data['sort_order'] !== ''
            ? (int) $data['sort_order']
            : $this->nextSortOrder((int) ($data['parent_id'] ?? 0), (string) ($data['locale'] ?? 'pl'));
        $base = [
            'parent_id' => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'locale' => (string) ($data['locale'] ?? 'pl'),
            'label' => trim((string) ($data['label'] ?? '')),
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'slug' => trim((string) ($data['slug'] ?? '')) ?: null,
            'href' => trim((string) ($data['href'] ?? '')) ?: null,
            'item_kind' => trim((string) ($data['item_kind'] ?? 'link')),
            'section_key' => trim((string) ($data['section_key'] ?? '')) ?: null,
            'content_type' => trim((string) ($data['content_type'] ?? '')) ?: null,
            'sort_order' => $sortOrder,
            'is_visible' => !empty($data['is_visible']) ? 1 : 0,
        ];

        if (!empty($data['id'])) {
            $base['id'] = (int) $data['id'];
            $statement = $pdo->prepare(
                'UPDATE navigation_items
                SET parent_id = :parent_id, locale = :locale, label = :label, title = :title, slug = :slug, href = :href,
                    item_kind = :item_kind, section_key = :section_key, content_type = :content_type,
                    sort_order = :sort_order, is_visible = :is_visible, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id'
            );
            $statement->execute($base);

            return (int) $data['id'];
        }

        $statement = $pdo->prepare(
            'INSERT INTO navigation_items
            (parent_id, locale, label, title, slug, href, item_kind, section_key, content_type, sort_order, is_visible)
            VALUES
            (:parent_id, :locale, :label, :title, :slug, :href, :item_kind, :section_key, :content_type, :sort_order, :is_visible)'
        );
        $statement->execute($base);

        return (int) $pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $statement = $this->database->pdo()->prepare('DELETE FROM navigation_items WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function setVisibility(int $id, bool $isVisible): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $statement = $this->database->pdo()->prepare(
            'UPDATE navigation_items
            SET is_visible = :is_visible, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_visible' => $isVisible ? 1 : 0,
        ]);
    }

    public function moveUp(int $id): void
    {
        $this->swapWithSibling($id, 'up');
    }

    public function moveDown(int $id): void
    {
        $this->swapWithSibling($id, 'down');
    }

    public function reorder(array $orders): void
    {
        if (!$this->tableExists() || $orders === []) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($orders): void {
            $statement = $pdo->prepare(
                'UPDATE navigation_items
                SET parent_id = :parent_id, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id'
            );

            foreach ($orders as $row) {
                $statement->execute([
                    'id' => (int) $row['id'],
                    'parent_id' => !empty($row['parent_id']) ? (int) $row['parent_id'] : null,
                    'sort_order' => (int) $row['sort_order'],
                ]);
            }
        });
    }

    public function replaceLocaleWithGroups(string $locale, array $groups): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($locale, $groups): void {
            $delete = $pdo->prepare('DELETE FROM navigation_items WHERE locale = :locale');
            $delete->execute(['locale' => $locale]);

            $insert = $pdo->prepare(
                'INSERT INTO navigation_items
                (parent_id, locale, label, title, slug, href, item_kind, section_key, content_type, sort_order, is_visible)
                VALUES
                (:parent_id, :locale, :label, :title, :slug, :href, :item_kind, :section_key, :content_type, :sort_order, :is_visible)'
            );

            foreach (array_values($groups) as $groupIndex => $group) {
                $children = array_values($group['children'] ?? []);
                $singleSelfChild = count($children) === 1
                    && (($children[0]['href'] ?? '') === ($group['href'] ?? ''))
                    && (($children[0]['section_key'] ?? '') === ($group['key'] ?? ''));

                $parentPayload = [
                    'parent_id' => null,
                    'locale' => $locale,
                    'label' => (string) ($group['label'] ?? ''),
                    'title' => (string) ($group['label'] ?? ''),
                    'slug' => $singleSelfChild ? ((string) ($children[0]['slug'] ?? '') ?: null) : null,
                    'href' => (string) ($group['href'] ?? ''),
                    'item_kind' => $singleSelfChild ? (string) ($children[0]['kind'] ?? 'page') : 'link',
                    'section_key' => (string) ($group['key'] ?? '') ?: null,
                    'content_type' => $singleSelfChild ? ((string) ($children[0]['content_type'] ?? '') ?: null) : null,
                    'sort_order' => ($groupIndex + 1) * 10,
                    'is_visible' => 1,
                ];
                $insert->execute($parentPayload);
                $parentId = (int) $pdo->lastInsertId();

                if ($singleSelfChild) {
                    continue;
                }

                foreach ($children as $childIndex => $child) {
                    $insert->execute([
                        'parent_id' => $parentId,
                        'locale' => $locale,
                        'label' => (string) ($child['label'] ?? ''),
                        'title' => (string) (($child['title'] ?? '') ?: ($child['label'] ?? '')),
                        'slug' => ((string) ($child['slug'] ?? '') ?: null),
                        'href' => (string) ($child['href'] ?? ''),
                        'item_kind' => (string) ($child['kind'] ?? 'link'),
                        'section_key' => ((string) ($child['section_key'] ?? '') ?: null),
                        'content_type' => ((string) ($child['content_type'] ?? '') ?: null),
                        'sort_order' => ($childIndex + 1) * 10,
                        'is_visible' => 1,
                    ]);
                }
            }
        });
    }

    private function buildTree(array $byParent, int $parentId): array
    {
        $tree = [];
        foreach ($byParent[$parentId] ?? [] as $item) {
            $item['children'] = $this->buildTree($byParent, (int) $item['id']);
            $tree[] = $item;
        }

        return $tree;
    }

    private function nextSortOrder(int $parentId, string $locale): int
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT COALESCE(MAX(sort_order), -10) FROM navigation_items WHERE locale = :locale AND COALESCE(parent_id, 0) = :parent_id'
        );
        $statement->execute([
            'locale' => $locale,
            'parent_id' => $parentId,
        ]);

        return ((int) $statement->fetchColumn()) + 10;
    }

    private function swapWithSibling(int $id, string $direction): void
    {
        $current = $this->find($id);
        if (!$current) {
            return;
        }

        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $statement = $this->database->pdo()->prepare(
            "SELECT * FROM navigation_items
            WHERE locale = :locale
              AND COALESCE(parent_id, 0) = :parent_id
              AND sort_order {$operator} :sort_order
            ORDER BY sort_order {$order}, id {$order}
            LIMIT 1"
        );
        $statement->execute([
            'locale' => $current['locale'],
            'parent_id' => (int) ($current['parent_id'] ?? 0),
            'sort_order' => (int) $current['sort_order'],
        ]);
        $sibling = $statement->fetch();

        if (!$sibling) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($current, $sibling): void {
            $update = $pdo->prepare('UPDATE navigation_items SET sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute([
                'id' => (int) $current['id'],
                'sort_order' => (int) $sibling['sort_order'],
            ]);
            $update->execute([
                'id' => (int) $sibling['id'],
                'sort_order' => (int) $current['sort_order'],
            ]);
        });
    }
}

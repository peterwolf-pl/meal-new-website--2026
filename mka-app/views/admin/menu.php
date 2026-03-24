<section class="admin-page-head">
    <div>
        <p class="section-kicker">Menu</p>
        <h1>Układ menu</h1>
    </div>
</section>

<?php if (!empty($errors)): ?>
    <div class="form-error-box">
        <p>Popraw pola formularza zaznaczone poniżej.</p>
    </div>
<?php endif; ?>

<section class="admin-panel-grid">
    <article class="admin-panel-card">
        <div class="panel-card-head">
            <h2>Wygląd kafelków</h2>
        </div>
        <form method="post" action="/admin/menu/appearance/save" class="admin-form-stack">
            <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
            <div class="admin-form-grid columns-2">
                <label>
                    <span>Kolor tła aktywnego i hover kafelka</span>
                    <input type="text" name="menu_active_background_color" value="<?= e((string) ($appearance['menu_active_background_color'] ?? '#ededed')) ?>" placeholder="#ededed">
                </label>
                <label>
                    <span>Kolor linii menu</span>
                    <input type="text" name="menu_line_color" value="<?= e((string) ($appearance['menu_line_color'] ?? '#cbbfb0')) ?>" placeholder="#cbbfb0">
                </label>
            </div>
            <div class="admin-submit-row">
                <button class="button-primary" type="submit">Zapisz wygląd menu</button>
            </div>
        </form>
    </article>
</section>

<section class="admin-panel-grid">
    <article class="admin-panel-card">
        <div class="panel-card-head">
            <div>
                <h2>Drzewo menu</h2>
                <p class="helper-note">Przeciągnij pozycje, aby zmienić kolejność lub przenieść je między poziomami drzewa. Upuść na listę główną albo w obszar submenu wybranej pozycji.</p>
            </div>
            <div class="menu-tree-toolbar">
                <form method="post" action="/admin/menu/import-current">
                    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                    <button class="button-secondary" type="submit">Importuj obecny układ</button>
                </form>
                <form method="post" action="/admin/menu/sync-en">
                    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                    <button class="button-secondary" type="submit">Synchronizuj PL do EN</button>
                </form>
                <form method="post" action="/admin/menu/reorder" id="menu-reorder-form">
                    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                    <input type="hidden" name="order_payload" value="[]">
                    <button class="button-primary" type="submit">Zapisz kolejność</button>
                </form>
            </div>
        </div>

        <?php
        $renderTree = static function (array $nodes, int $depth = 0, int $parentId = 0) use (&$renderTree, $app): string {
            ob_start();
            ?>
            <ul class="menu-tree<?= $depth > 0 ? ' is-subtree' : '' ?>" data-menu-tree="<?= e((string) $depth) ?>" data-parent-node-id="<?= e((string) $parentId) ?>">
                <?php if ($nodes !== []): ?>
                    <?php foreach ($nodes as $node): ?>
                        <li class="menu-tree-item" data-menu-item data-id="<?= e((string) $node['id']) ?>" data-parent-id="<?= e((string) ($node['parent_id'] ?? 0)) ?>">
                            <div class="menu-tree-card">
                                <button class="menu-drag-handle" type="button" draggable="true" aria-label="Przeciągnij pozycję">↕</button>
                                <div class="menu-tree-copy">
                                    <strong><?= e((string) $node['label']) ?></strong>
                                    <p class="helper-note">
                                        <?= empty($node['parent_id']) ? 'menu główne' : 'submenu' ?>
                                        · <?= e((string) $node['item_kind']) ?>
                                        <?php if (!empty($node['href'])): ?>
                                            · <?= e((string) $node['href']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <form method="post" action="/admin/menu/<?= e((string) $node['id']) ?>/visibility" class="menu-tree-visibility-form">
                                        <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                                        <input type="hidden" name="is_visible" value="0">
                                        <label class="menu-tree-visibility-toggle">
                                            <input type="checkbox" name="is_visible" value="1" <?= checked(!empty($node['is_visible'])) ?> data-autosubmit>
                                            <span><?= !empty($node['is_visible']) ? 'Pokazuj na stronie' : 'Ukryta na stronie' ?></span>
                                        </label>
                                    </form>
                                </div>
                                <div class="menu-tree-actions">
                                    <form method="post" action="/admin/menu/<?= e((string) $node['id']) ?>/up">
                                        <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                                        <button class="button-ghost" type="submit">W górę</button>
                                    </form>
                                    <form method="post" action="/admin/menu/<?= e((string) $node['id']) ?>/down">
                                        <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                                        <button class="button-ghost" type="submit">W dół</button>
                                    </form>
                                    <a class="button-ghost" href="/admin/menu/<?= e((string) $node['id']) ?>">Edytuj</a>
                                    <form method="post" action="/admin/menu/<?= e((string) $node['id']) ?>/delete">
                                        <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                                        <button class="button-ghost" type="submit">Usuń</button>
                                    </form>
                                </div>
                            </div>
                            <div class="menu-tree-dropzone" data-dropzone-label="Upuść tutaj jako submenu pozycji „<?= e((string) $node['label']) ?>”">
                                <?= $renderTree($node['children'] ?? [], $depth + 1, (int) $node['id']) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            <?php

            return (string) ob_get_clean();
        };
        ?>

        <?php if (empty($items)): ?>
            <p class="helper-note">Menu z bazy jest jeszcze puste. Po dodaniu pierwszych pozycji frontend zacznie korzystać z układu z CMS.</p>
        <?php else: ?>
            <div class="menu-tree-wrap" data-menu-manager>
                <div class="menu-tree-root-dropzone">Przeciągnij tutaj, aby przenieść pozycję na poziom główny.</div>
                <?= $renderTree($items) ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="panel-card-head">
            <h2><?= $editing ? 'Edytuj pozycję' : 'Nowa pozycja' ?></h2>
        </div>
        <form method="post" action="/admin/menu/save" class="admin-form-stack">
            <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
            <input type="hidden" name="id" value="<?= e((string) ($form['id'] ?? '')) ?>">

            <div class="admin-form-grid columns-2">
                <label>
                    <span>Poziom nadrzędny</span>
                    <select name="parent_id">
                        <option value="">- pozycja główna -</option>
                        <?php foreach ($parentOptions as $option): ?>
                            <option value="<?= e((string) $option['id']) ?>" <?= ((int) ($form['parent_id'] ?? 0) === (int) $option['id']) ? 'selected' : '' ?>>
                                <?= e((string) $option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Etykieta</span>
                    <input type="text" name="label" value="<?= e((string) ($form['label'] ?? '')) ?>" required>
                    <?php if (!empty($errors['label'])): ?><small class="form-error"><?= e($errors['label']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Tytuł techniczny</span>
                    <input type="text" name="title" value="<?= e((string) ($form['title'] ?? '')) ?>">
                </label>
                <label>
                    <span>Slug</span>
                    <input type="text" name="slug" value="<?= e((string) ($form['slug'] ?? '')) ?>">
                </label>
                <label>
                    <span>URL</span>
                    <input type="text" name="href" value="<?= e((string) ($form['href'] ?? '')) ?>" placeholder="/pl/...">
                </label>
                <label>
                    <span>Typ pozycji</span>
                    <select name="item_kind">
                        <option value="page" <?= selected('page', (string) ($form['item_kind'] ?? '')) ?>>Page</option>
                        <option value="listing" <?= selected('listing', (string) ($form['item_kind'] ?? '')) ?>>Listing</option>
                        <option value="link" <?= selected('link', (string) ($form['item_kind'] ?? '')) ?>>Link</option>
                    </select>
                    <?php if (!empty($errors['item_kind'])): ?><small class="form-error"><?= e($errors['item_kind']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Section key</span>
                    <input type="text" name="section_key" value="<?= e((string) ($form['section_key'] ?? '')) ?>" placeholder="MUZEUM / PROGRAM / ...">
                </label>
                <label>
                    <span>Content type</span>
                    <input type="text" name="content_type" value="<?= e((string) ($form['content_type'] ?? '')) ?>" placeholder="EXHIBITION / EVENT / ...">
                </label>
                <label class="checkbox-field span-2">
                    <input type="checkbox" name="is_visible" value="1" <?= checked(!empty($form['is_visible'])) ?>>
                    <span>Widoczna pozycja</span>
                </label>
            </div>

            <div class="admin-submit-row">
                <button class="button-primary" type="submit">Zapisz pozycję</button>
                <?php if ($editing): ?>
                    <a class="button-secondary" href="/admin/menu">Nowa pozycja</a>
                <?php endif; ?>
            </div>
        </form>
    </article>
</section>

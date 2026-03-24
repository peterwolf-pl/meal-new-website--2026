<?php $homeUrl = '/' . $locale; ?>
<main id="main-content" class="accordion-main" data-accordion-root>
    <section class="accordion-shell" aria-label="Sekcje muzeum">
        <?php foreach ($accordionSections as $section): ?>
            <article
                class="accordion-row<?= $section['is_active'] ? ' is-active' : '' ?>"
                id="section-<?= e(strtolower($section['key'])) ?>"
                data-section-row
                data-section-key="<?= e($section['key']) ?>"
            >
                <h2 class="accordion-row-heading">
                    <?php if ($section['has_subnav']): ?>
                        <button
                            class="accordion-trigger<?= $section['is_active'] ? ' is-active' : '' ?>"
                            type="button"
                            data-section-toggle
                            aria-expanded="<?= $section['is_active'] ? 'true' : 'false' ?>"
                            aria-controls="submenu-<?= e(strtolower($section['key'])) ?>"
                        >
                            <span class="accordion-row-title"><?= e($section['label']) ?></span>
                        </button>
                    <?php else: ?>
                        <button
                            class="accordion-trigger<?= $section['is_active'] ? ' is-active' : '' ?>"
                            type="button"
                            data-section-single-toggle
                            data-open-url="<?= e($section['href']) ?>"
                            data-close-url="<?= e($homeUrl) ?>"
                            aria-expanded="<?= $section['is_active'] ? 'true' : 'false' ?>"
                            aria-controls="panel-<?= e(strtolower($section['key'])) ?>"
                        >
                            <span class="accordion-row-title"><?= e($section['label']) ?></span>
                        </button>
                    <?php endif; ?>
                </h2>

                <?php $activeChild = $section['active_child']; ?>

                <?php if ($section['has_subnav']): ?>
                    <div class="accordion-submenu-panel<?= $section['is_active'] ? ' is-open' : '' ?>" id="submenu-<?= e(strtolower($section['key'])) ?>" data-section-submenu>
                        <div class="accordion-subnav accordion-subnav-inline" aria-label="<?= e($section['label']) ?>">
                            <?php foreach ($section['children'] as $child): ?>
                                <article class="accordion-subnav-row<?= $child['is_active'] ? ' is-active is-open' : '' ?>">
                                    <?php if ($child['is_active']): ?>
                                        <div class="accordion-subnav-item is-active">
                                            <span class="accordion-subnav-title"><?= e($child['label']) ?></span>
                                            <a
                                                class="accordion-subnav-close"
                                                href="<?= e($homeUrl . '#section-' . strtolower($section['key'])) ?>"
                                                data-accordion-nav
                                                aria-label="Zamknij treść sekcji"
                                            >
                                                <span aria-hidden="true">×</span>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <a class="accordion-subnav-item" href="<?= e($child['href']) ?>" data-accordion-nav>
                                            <span class="accordion-subnav-title"><?= e($child['label']) ?></span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($child['is_active']): ?>
                                        <div class="accordion-subnav-content" id="submenu-item-<?= e($section['key'] . '-' . ($child['slug'] ?? 'item')) ?>">
                                            <?php if ($child['kind'] === 'listing'): ?>
                                                <section class="accordion-listing-shell">
                                                    <div class="accordion-listing-intro">
                                                        <?= $app->view()->partial('partials/accordion_entry', [
                                                            'entry' => $child['intro'],
                                                            'headingTag' => 'h3',
                                                            'showTitle' => true,
                                                            'entryLabel' => $app->navigation()->typeLabel($child['content_type']),
                                                        ]) ?>
                                                    </div>

                                                    <div class="accordion-listing-grid">
                                                        <div class="accordion-item-list" aria-label="<?= e($child['title']) ?>">
                                                            <?php if ($child['items'] === []): ?>
                                                                <div class="accordion-list-empty">
                                                                    <p>Brak opublikowanych pozycji w tej sekcji.</p>
                                                                </div>
                                                            <?php endif; ?>

                                                            <?php foreach ($child['items'] as $item): ?>
                                                                <?php $itemIsActive = ($accordionActive['item_slug'] ?? null) === ($item['slug'] ?? null); ?>
                                                                <?php if ($itemIsActive): ?>
                                                                    <div class="accordion-item-link is-active">
                                                                        <p class="accordion-item-type"><?= e($app->navigation()->typeLabel($item['content_type'])) ?></p>
                                                                        <h4><?= e($item['title']) ?></h4>
                                                                        <?php if (!empty($item['summary'])): ?>
                                                                            <p><?= e($item['summary']) ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <a class="accordion-item-link" href="<?= e($app->contentService()->relativeUrl($item, $locale)) ?>" data-accordion-nav>
                                                                        <p class="accordion-item-type"><?= e($app->navigation()->typeLabel($item['content_type'])) ?></p>
                                                                        <h4><?= e($item['title']) ?></h4>
                                                                        <?php if (!empty($item['summary'])): ?>
                                                                            <p><?= e($item['summary']) ?></p>
                                                                        <?php endif; ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>

                                                        <div class="accordion-item-detail">
                                                            <?php if (!empty($child['selected_item'])): ?>
                                                                <?= $app->view()->partial('partials/accordion_entry', [
                                                                    'entry' => $child['selected_item'],
                                                                    'headingTag' => 'h3',
                                                                    'showTitle' => true,
                                                                    'entryLabel' => $app->navigation()->typeLabel($child['selected_item']['content_type']),
                                                                ]) ?>
                                                            <?php else: ?>
                                                                <div class="accordion-empty-state">
                                                                    <p class="accordion-kicker"><?= e($child['title']) ?></p>
                                                                    <?php if ($child['items'] === []): ?>
                                                                        <h3>Treść w przygotowaniu</h3>
                                                                        <p>Ta część programu zostanie uzupełniona w panelu CMS.</p>
                                                                    <?php else: ?>
                                                                        <h3>Wybierz element z listy</h3>
                                                                        <p>Po lewej stronie znajdziesz wszystkie opublikowane pozycje tej sekcji.</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </section>
                                            <?php else: ?>
                                                <?= $app->view()->partial('partials/accordion_entry', [
                                                    'entry' => $child['entry'],
                                                    'headingTag' => 'h3',
                                                    'showTitle' => true,
                                                    'entryLabel' => $section['label'],
                                                ]) ?>
                                                <?php if (!empty($child['employees'])): ?>
                                                    <?= $app->view()->partial('partials/employee_cards', [
                                                        'employees' => $child['employees'],
                                                    ]) ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($section['is_active']): ?>
                    <div class="accordion-panel" id="panel-<?= e(strtolower($section['key'])) ?>">
                        <div class="accordion-panel-head">
                            <p class="accordion-panel-label"><?= e($section['label']) ?></p>
                            <a class="accordion-close" href="<?= e($homeUrl) ?>" data-accordion-nav aria-label="Zamknij sekcję">
                                <span aria-hidden="true">×</span>
                            </a>
                        </div>

                        <div class="accordion-panel-layout is-single">
                            <div class="accordion-panel-content">
                                <?php if ($activeChild !== null): ?>
                                    <?= $app->view()->partial('partials/accordion_entry', [
                                        'entry' => $activeChild['entry'],
                                        'headingTag' => 'h3',
                                        'showTitle' => true,
                                        'entryLabel' => null,
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<main id="main-content" class="page-frame home-frame">
    <section class="hero-grid">
        <div class="hero-copy editorial-card">
            <p class="section-kicker">Muzeum książki artystycznej</p>
            <h1><?= e($settings['translation']['homepage_title']) ?></h1>
            <p class="hero-lead"><?= e($settings['translation']['homepage_lead']) ?></p>
            <div class="richtext"><?= $settings['translation']['homepage_intro'] ?></div>
            <div class="hero-actions">
                <a class="button-primary" href="/<?= e($locale) ?>/program/wystawy">Zobacz program</a>
                <a class="button-secondary" href="/<?= e($locale) ?>/wizyta">Zaplanuj wizytę</a>
            </div>
        </div>
        <div class="hero-media editorial-card">
            <?php if (!empty($settings['hero_media'])): ?>
                <?php if ($settings['hero_media']['kind'] === 'image'): ?>
                    <img src="<?= e($settings['hero_media']['public_url']) ?>" alt="<?= e($settings['hero_media']['alt_text'] ?? '') ?>">
                <?php else: ?>
                    <div class="placeholder-media">
                        <p><?= e($settings['hero_media']['title'] ?? 'Hero media') ?></p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="placeholder-media">
                    <p>MKA</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section-block">
        <div class="section-header">
            <p class="section-kicker">Wyróżnione</p>
            <h2>Aktualnie w centrum uwagi</h2>
        </div>
        <div class="feature-grid">
            <?php foreach ($featured as $item): ?>
                <article class="feature-card">
                    <p class="feature-meta"><?= e($app->navigation()->typeLabel($item['content_type'])) ?></p>
                    <h3><a href="<?= e($app->contentService()->relativeUrl($item, $locale)) ?>"><?= e($item['title']) ?></a></h3>
                    <p><?= e($item['summary'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="split-panel">
        <div class="list-column">
            <div class="section-header">
                <p class="section-kicker">Program</p>
                <h2>Trwające wystawy</h2>
            </div>
            <div class="stack-list">
                <?php foreach ($exhibitions as $item): ?>
                    <article class="stack-card">
                        <h3><a href="<?= e($app->contentService()->relativeUrl($item, $locale)) ?>"><?= e($item['title']) ?></a></h3>
                        <?php if (!empty($item['event_start'])): ?>
                            <p class="stack-meta"><?= e(date('d.m.Y H:i', strtotime((string) $item['event_start']))) ?></p>
                        <?php endif; ?>
                        <p><?= e($item['summary'] ?? '') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="list-column">
            <div class="section-header">
                <p class="section-kicker">Program</p>
                <h2>Nadchodzące wydarzenia</h2>
            </div>
            <div class="stack-list">
                <?php foreach ($events as $item): ?>
                    <article class="stack-card">
                        <h3><a href="<?= e($app->contentService()->relativeUrl($item, $locale)) ?>"><?= e($item['title']) ?></a></h3>
                        <?php if (!empty($item['event_start'])): ?>
                            <p class="stack-meta"><?= e(date('d.m.Y H:i', strtotime((string) $item['event_start']))) ?><?php if (!empty($item['event_location'])): ?>, <?= e($item['event_location']) ?><?php endif; ?></p>
                        <?php endif; ?>
                        <p><?= e($item['summary'] ?? '') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-header">
            <p class="section-kicker">Kolekcja</p>
            <h2>Wybrane obiekty</h2>
        </div>
        <div class="feature-grid collection-grid">
            <?php foreach ($collections as $item): ?>
                <article class="feature-card">
                    <p class="feature-meta"><?= e($item['collection_group'] ?: 'MKA') ?></p>
                    <h3><a href="<?= e($app->contentService()->relativeUrl($item, $locale)) ?>"><?= e($item['title']) ?></a></h3>
                    <?php if (!empty($item['creator_name'])): ?>
                        <p class="stack-meta"><?= e($item['creator_name']) ?><?php if (!empty($item['item_year'])): ?>, <?= e($item['item_year']) ?><?php endif; ?></p>
                    <?php endif; ?>
                    <p><?= e($item['summary'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<main id="main-content" class="page-frame">
    <?= $app->view()->partial('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

    <section class="listing-hero editorial-card">
        <p class="section-kicker"><?= e($app->navigation()->typeLabel($listingType)) ?></p>
        <h1><?= e($intro['title']) ?></h1>
        <?php if (!empty($intro['summary'])): ?>
            <p class="detail-summary"><?= e($intro['summary']) ?></p>
        <?php endif; ?>
        <div class="richtext"><?= $intro['body'] ?></div>
    </section>

    <section class="listing-table">
        <?php foreach ($items as $item): ?>
            <article class="listing-row">
                <div class="listing-row-main">
                    <p class="feature-meta"><?= e($app->navigation()->typeLabel($item['content_type'])) ?></p>
                    <h2><a href="<?= e($app->contentService()->relativeUrl($item, $locale)) ?>"><?= e($item['title']) ?></a></h2>
                    <p><?= e($item['summary'] ?? '') ?></p>
                </div>
                <div class="listing-row-side">
                    <?php if (!empty($item['event_start'])): ?>
                        <p><?= e(date('d.m.Y H:i', strtotime((string) $item['event_start']))) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($item['event_location'])): ?>
                        <p><?= e($item['event_location']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($item['creator_name'])): ?>
                        <p><?= e($item['creator_name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($item['item_year'])): ?>
                        <p><?= e($item['item_year']) ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

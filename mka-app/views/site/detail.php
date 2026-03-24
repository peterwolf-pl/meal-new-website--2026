<main id="main-content" class="page-frame">
    <?= $app->view()->partial('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

    <article class="detail-shell detail-shell-wide">
        <header class="detail-header">
            <p class="section-kicker"><?= e($app->navigation()->typeLabel($entry['content_type'])) ?></p>
            <h1><?= e($entry['title']) ?></h1>
            <div class="detail-meta">
                <?php if (!empty($entry['event_start'])): ?>
                    <span><?= e(date('d.m.Y H:i', strtotime((string) $entry['event_start']))) ?></span>
                <?php endif; ?>
                <?php if (!empty($entry['event_location'])): ?>
                    <span><?= e($entry['event_location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($entry['creator_name'])): ?>
                    <span><?= e($entry['creator_name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($entry['item_year'])): ?>
                    <span><?= e($entry['item_year']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($entry['summary'])): ?>
                <p class="detail-summary"><?= e($entry['summary']) ?></p>
            <?php endif; ?>
        </header>

        <div class="detail-grid detail-grid-wide">
            <div class="richtext detail-richtext"><?= $entry['body'] ?></div>
            <?php if (!empty($entry['media'])): ?>
                <aside class="detail-gallery">
                    <?php foreach ($entry['media'] as $asset): ?>
                        <figure class="gallery-card">
                            <?php if ($asset['kind'] === 'image'): ?>
                                <img src="<?= e($asset['public_url']) ?>" alt="<?= e($asset['is_decorative'] ? '' : ($asset['alt_text'] ?? '')) ?>">
                            <?php else: ?>
                                <a class="media-link" href="<?= e($asset['public_url']) ?>" target="_blank" rel="noreferrer">
                                    <?= e($asset['title']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($asset['caption'])): ?>
                                <figcaption><?= e($asset['caption']) ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </aside>
            <?php endif; ?>
        </div>
    </article>
</main>

<main id="main-content" class="page-frame">
    <?= $app->view()->partial('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>
    <article class="detail-shell">
        <header class="detail-header">
            <p class="section-kicker"><?= e($app->navigation()->sectionLabel($entry['section_key'])) ?></p>
            <h1><?= e($entry['title']) ?></h1>
            <?php if (!empty($entry['summary'])): ?>
                <p class="detail-summary"><?= e($entry['summary']) ?></p>
            <?php endif; ?>
        </header>

        <div class="detail-grid">
            <div class="richtext detail-richtext"><?= $entry['body'] ?></div>
            <?php if (!empty($entry['media'])): ?>
                <aside class="detail-aside">
                    <?php foreach ($entry['media'] as $asset): ?>
                        <figure class="media-card">
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

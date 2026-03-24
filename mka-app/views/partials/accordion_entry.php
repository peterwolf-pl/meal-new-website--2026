<?php
$headingTag = $headingTag ?? 'h3';
$showTitle = $showTitle ?? true;
$entryLabel = $entryLabel ?? null;
$body = trim((string) ($entry['body'] ?? '')) !== '' ? (string) $entry['body'] : '<p>Treść w przygotowaniu.</p>';
$meta = array_filter([
    !empty($entry['event_start']) ? date('d.m.Y H:i', strtotime((string) $entry['event_start'])) : null,
    $entry['event_location'] ?? null,
    $entry['creator_name'] ?? null,
    $entry['item_year'] ?? null,
    $entry['collection_group'] ?? null,
]);
?>
<article class="accordion-entry<?= !empty($entry['media']) ? ' has-media' : '' ?>">
    <?php if ($entryLabel !== null || $showTitle || !empty($entry['summary']) || $meta !== []): ?>
        <header class="accordion-entry-head">
            <?php if ($entryLabel !== null): ?>
                <p class="accordion-kicker"><?= e($entryLabel) ?></p>
            <?php endif; ?>
            <?php if ($showTitle): ?>
                <<?= e($headingTag) ?> class="accordion-entry-title"><?= e($entry['title'] ?? '') ?></<?= e($headingTag) ?>>
            <?php endif; ?>
            <?php if (!empty($entry['summary'])): ?>
                <p class="accordion-summary"><?= e($entry['summary']) ?></p>
            <?php endif; ?>
            <?php if ($meta !== []): ?>
                <div class="accordion-meta">
                    <?php foreach ($meta as $item): ?>
                        <span><?= e($item) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>
    <?php endif; ?>

    <div class="accordion-entry-grid<?= empty($entry['media']) ? ' is-single' : '' ?>">
        <div class="richtext accordion-richtext"><?= $body ?></div>
        <?php if (!empty($entry['media'])): ?>
            <aside class="accordion-media-stack">
                <?php foreach ($entry['media'] as $asset): ?>
                    <figure class="accordion-media-card">
                        <?php if (($asset['kind'] ?? '') === 'image'): ?>
                            <img src="<?= e($asset['public_url']) ?>" alt="<?= e(!empty($asset['is_decorative']) ? '' : ($asset['alt_text'] ?? '')) ?>">
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

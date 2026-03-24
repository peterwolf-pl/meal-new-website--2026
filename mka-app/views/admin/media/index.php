<section class="admin-page-head">
    <div>
        <p class="section-kicker">Media</p>
        <h1>Biblioteka mediów</h1>
    </div>
    <div class="admin-actions">
        <a class="button-primary" href="/admin/media/new">Dodaj medium</a>
    </div>
</section>

<div class="media-library-grid">
    <?php foreach ($items as $asset): ?>
        <a class="media-library-card" href="/admin/media/<?= e((string) $asset['id']) ?>">
            <?php if (($asset['kind'] ?? '') === 'image' && !empty($asset['public_url'])): ?>
                <div class="media-library-thumb">
                    <img src="<?= e((string) $asset['public_url']) ?>" alt="<?= e((string) ($asset['alt_text'] ?? $asset['title'])) ?>">
                </div>
            <?php endif; ?>
            <span class="feature-meta"><?= e(strtoupper((string) $asset['kind'])) ?></span>
            <strong><?= e($asset['title']) ?></strong>
            <?php if (!empty($asset['caption'])): ?>
                <p><?= e($asset['caption']) ?></p>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

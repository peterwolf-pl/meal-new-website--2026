<?php
$googleInsights ??= [
    'tracking' => ['mode' => 'none', 'label' => 'Brak'],
    'source' => ['mode' => 'none', 'label' => 'Brak autoryzacji Google API', 'email' => ''],
    'period' => ['label' => 'ostatnie 28 dni'],
    'analytics' => ['enabled' => false, 'connected' => false, 'summary' => null, 'top_pages' => [], 'error' => null],
    'search_console' => ['enabled' => false, 'connected' => false, 'summary' => null, 'top_queries' => [], 'error' => null],
    'updated_at' => null,
    'from_cache' => false,
];
$ga = $googleInsights['analytics'];
$gsc = $googleInsights['search_console'];
$tracking = $googleInsights['tracking'];
$source = $googleInsights['source'];
$formatInt = static fn(?int $value): string => number_format((int) ($value ?? 0), 0, ',', ' ');
$formatPercent = static fn(?float $value): string => number_format((float) ($value ?? 0) * 100, 1, ',', ' ') . '%';
$updatedAt = !empty($googleInsights['updated_at']) ? date('Y-m-d H:i', strtotime((string) $googleInsights['updated_at'])) : null;
?>

<section class="admin-page-head">
    <div>
        <p class="section-kicker">Panel</p>
        <h1>CMS muzeum</h1>
    </div>
    <div class="admin-actions">
        <a class="button-primary" href="/admin/content/new">Nowa treść</a>
        <a class="button-secondary" href="/admin/media/new">Nowe medium</a>
    </div>
</section>

<section class="admin-stats">
    <article class="admin-stat-card">
        <p class="section-kicker">Treści</p>
        <h2><?= e((string) $contentCount) ?></h2>
    </article>
    <article class="admin-stat-card">
        <p class="section-kicker">Media</p>
        <h2><?= e((string) $mediaCount) ?></h2>
    </article>
    <article class="admin-stat-card">
        <p class="section-kicker">Szkice</p>
        <h2><?= e((string) $statusCounts['draft']) ?></h2>
    </article>
    <article class="admin-stat-card">
        <p class="section-kicker">Opublikowane</p>
        <h2><?= e((string) $statusCounts['published']) ?></h2>
    </article>
    <article class="admin-stat-card">
        <p class="section-kicker">Tracking</p>
        <h2><?= e($tracking['label']) ?></h2>
    </article>
    <?php if ($ga['connected'] && !empty($ga['summary'])): ?>
        <article class="admin-stat-card">
            <p class="section-kicker">GA użytkownicy</p>
            <h2><?= e($formatInt($ga['summary']['active_users'] ?? 0)) ?></h2>
        </article>
        <article class="admin-stat-card">
            <p class="section-kicker">GA sesje</p>
            <h2><?= e($formatInt($ga['summary']['sessions'] ?? 0)) ?></h2>
        </article>
        <article class="admin-stat-card">
            <p class="section-kicker">GA odsłony</p>
            <h2><?= e($formatInt($ga['summary']['screen_page_views'] ?? 0)) ?></h2>
        </article>
    <?php endif; ?>
    <?php if ($gsc['connected'] && !empty($gsc['summary'])): ?>
        <article class="admin-stat-card">
            <p class="section-kicker">GSC kliknięcia</p>
            <h2><?= e($formatInt($gsc['summary']['clicks'] ?? 0)) ?></h2>
        </article>
        <article class="admin-stat-card">
            <p class="section-kicker">GSC CTR</p>
            <h2><?= e($formatPercent($gsc['summary']['ctr'] ?? 0.0)) ?></h2>
        </article>
    <?php endif; ?>
</section>

<section class="admin-panel-grid">
    <article class="admin-panel-card">
        <div class="panel-card-head">
            <h2>Ostatnio edytowane treści</h2>
            <a href="/admin/content">Zobacz wszystko</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Tytuł</th>
                    <th>Typ</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latestContent as $item): ?>
                    <tr>
                        <td><a href="/admin/content/<?= e((string) $item['id']) ?>"><?= e($item['title']) ?></a></td>
                        <td><?= e($app->navigation()->typeLabel($item['content_type'])) ?></td>
                        <td><span class="status-pill status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="admin-panel-card">
        <div class="panel-card-head">
            <h2>Ostatnio dodane media</h2>
            <a href="/admin/media">Biblioteka mediów</a>
        </div>
        <div class="media-mini-grid">
            <?php foreach ($latestMedia as $asset): ?>
                <a class="media-mini-card" href="/admin/media/<?= e((string) $asset['id']) ?>">
                    <span class="feature-meta"><?= e(strtoupper((string) $asset['kind'])) ?></span>
                    <strong><?= e($asset['title']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="admin-panel-grid">
    <article class="admin-panel-card">
        <div class="panel-card-head">
            <div>
                <h2>Google Analytics</h2>
                <p class="helper-note">
                    Zakres: <?= e($googleInsights['period']['label'] ?? 'ostatnie 28 dni') ?>
                    • źródło: <?= e($source['label'] ?? 'Brak autoryzacji Google API') ?><?= !empty($source['email']) ? ' (' . e($source['email']) . ')' : '' ?>
                    <?php if ($updatedAt): ?>
                        • aktualizacja <?= e($updatedAt) ?><?= !empty($googleInsights['from_cache']) ? ' (cache)' : '' ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($ga['connected'] && !empty($ga['summary'])): ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Strona</th>
                        <th>Wyświetlenia</th>
                        <th>Użytkownicy</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ga['top_pages'] as $page): ?>
                        <tr>
                            <td><?= e($page['page_path'] ?: '/') ?></td>
                            <td><?= e($formatInt($page['screen_page_views'] ?? 0)) ?></td>
                            <td><?= e($formatInt($page['active_users'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($ga['top_pages'] === []): ?>
                <p class="helper-note">Google Analytics nie zwrócił jeszcze danych o najpopularniejszych podstronach dla wybranego zakresu.</p>
            <?php endif; ?>
        <?php elseif (!empty($ga['error'])): ?>
            <p class="form-error"><?= e($ga['error']) ?></p>
        <?php else: ?>
            <p class="helper-note">Połącz Google w ustawieniach albo uzupełnij <code>GA4 Property ID</code> i konto serwisowe, aby pokazać statystyki Google Analytics w CMS.</p>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="panel-card-head">
            <div>
                <h2>Top keyword searches</h2>
                <p class="helper-note">Dane z Google Search Console dla tego samego zakresu dat.</p>
            </div>
        </div>

        <?php if ($gsc['connected'] && $gsc['top_queries'] !== []): ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Zapytanie</th>
                        <th>Kliknięcia</th>
                        <th>Impresje</th>
                        <th>CTR</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($gsc['top_queries'] as $query): ?>
                        <tr>
                            <td><?= e($query['query']) ?></td>
                            <td><?= e($formatInt($query['clicks'] ?? 0)) ?></td>
                            <td><?= e($formatInt($query['impressions'] ?? 0)) ?></td>
                            <td><?= e($formatPercent($query['ctr'] ?? 0.0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($gsc['connected']): ?>
            <p class="helper-note">Search Console jest połączony, ale dla wybranego zakresu nie ma jeszcze danych o zapytaniach.</p>
        <?php elseif (!empty($gsc['error'])): ?>
            <p class="form-error"><?= e($gsc['error']) ?></p>
        <?php else: ?>
            <p class="helper-note">Połącz Google w ustawieniach albo uzupełnij <code>Search Console Property</code> i konto serwisowe, aby zobaczyć topowe zapytania organiczne w CMS.</p>
        <?php endif; ?>
    </article>
</section>

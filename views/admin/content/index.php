<section class="admin-page-head">
    <div>
        <p class="section-kicker">Treści</p>
        <h1>Biblioteka treści</h1>
    </div>
    <div class="admin-actions">
        <a class="button-primary" href="/admin/content/new">Nowa treść</a>
    </div>
</section>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
        <tr>
            <th>Tytuł</th>
            <th>Sekcja</th>
            <th>Typ</th>
            <th>Status</th>
            <th>Kolejność</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><a href="/admin/content/<?= e((string) $item['id']) ?>"><?= e($item['title'] ?: '[bez tytułu]') ?></a></td>
                <td><?= e($app->navigation()->sectionLabel($item['section_key'])) ?></td>
                <td><?= e($app->navigation()->typeLabel($item['content_type'])) ?></td>
                <td><span class="status-pill status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span></td>
                <td><?= e((string) $item['sort_order']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

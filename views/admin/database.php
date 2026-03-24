<section class="admin-page-head">
    <div>
        <p class="section-kicker">Baza danych</p>
        <h1>Aktualizacja migracji SQL</h1>
    </div>
</section>

<section class="admin-form-card">
    <div class="panel-card-head">
        <div>
            <h2>Status bazy</h2>
            <p class="helper-note">Sterownik: <code><?= e((string) ($status['driver'] ?? 'mysql')) ?></code></p>
        </div>
        <form method="post" action="/admin/database/migrate">
            <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
            <button class="button-primary" type="submit">Aktualizuj bazę</button>
        </form>
    </div>

    <div class="admin-form-grid columns-2">
        <div>
            <h2>Oczekujące migracje</h2>
            <?php if (empty($status['pending'])): ?>
                <p class="helper-note">Brak oczekujących zmian.</p>
            <?php else: ?>
                <ul class="admin-simple-list">
                    <?php foreach ($status['pending'] as $migration): ?>
                        <li><code><?= e((string) $migration) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div>
            <h2>Wykonane migracje</h2>
            <?php if (empty($status['applied'])): ?>
                <p class="helper-note">Nie wykonano jeszcze żadnych migracji.</p>
            <?php else: ?>
                <ul class="admin-simple-list">
                    <?php foreach ($status['applied'] as $migration): ?>
                        <li><code><?= e((string) $migration) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

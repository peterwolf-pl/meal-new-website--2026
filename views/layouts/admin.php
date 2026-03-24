<!DOCTYPE html>
<?php
$adminLocale = $app->localeOrDefault(is_string($_SESSION['admin_locale'] ?? null) ? $_SESSION['admin_locale'] : null);
$returnPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
$returnQuery = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_QUERY);
if (is_string($returnQuery) && $returnQuery !== '') {
    $returnPath .= '?' . $returnQuery;
}
?>
<html lang="<?= e($adminLocale) ?>">
<head>
    <?php
    $cssVersion = @filemtime($app->path('root') . '/../public_html/css/site.css') ?: time();
    $adminJsVersion = @filemtime($app->path('root') . '/../public_html/js/admin.js') ?: time();
    $loadTinyMce = !empty($loadTinyMce);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle ?? 'CMS') ?> | CMS</title>
    <link rel="stylesheet" href="/css/site.css?v=<?= e((string) $cssVersion) ?>">
    <?php if ($loadTinyMce): ?>
        <?php $tinyMceApiKey = (string) $app->config('integrations.tinymce_api_key', 'no-api-key'); ?>
        <script src="https://cdn.tiny.cloud/1/<?= e($tinyMceApiKey) ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <?php endif; ?>
    <script defer src="/js/admin.js?v=<?= e((string) $adminJsVersion) ?>"></script>
</head>
<body class="admin-shell">
<?php if ($authUser): ?>
    <header class="admin-header-shell">
        <div class="admin-bar">
            <a class="admin-brand" href="/<?= e($adminLocale) ?>">Strona główna</a>
            <nav class="admin-nav">
                <a href="/admin">Dashboard</a>
                <a href="/admin/database">Baza</a>
                <a href="/admin/content">Treści</a>
                <a href="/admin/media">Media</a>
                <a href="/admin/menu">Układ menu</a>
                <a href="/admin/settings">Ustawienia</a>
                <a href="/admin/chrome">Header i footer</a>
                <a href="/admin/typography">Typografia</a>
            </nav>
            <div class="admin-header-tools">
                <div class="admin-locale-switch" aria-label="Język CMS">
                    <a href="/admin/locale/pl?return=<?= e($returnPath) ?>" class="<?= $adminLocale === 'pl' ? 'is-active' : '' ?>">PL</a>
                    <a href="/admin/locale/en?return=<?= e($returnPath) ?>" class="<?= $adminLocale === 'en' ? 'is-active' : '' ?>">EN</a>
                </div>
                <form method="post" action="/admin/logout">
                    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
                    <button class="button-ghost" type="submit">Wyloguj</button>
                </form>
            </div>
        </div>
    </header>
<?php endif; ?>
<main class="admin-main-shell">
    <?= $app->view()->partial('partials/flash', ['flashMessages' => $flashMessages]) ?>
    <?= $content ?>
</main>
</body>
</html>

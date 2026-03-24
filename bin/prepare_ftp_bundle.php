<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$publicSource = $root . '/public';
$appSourceDirectories = [
    'config',
    'database',
    'src',
    'views',
];
$storageSource = $root . '/storage';
$publicTarget = $root . '/public_html';
$appTarget = $root . '/mka-app';

recreateDirectory($publicTarget);
recreateDirectory($appTarget);

copyDirectory($publicSource, $publicTarget, [
    $publicSource . '/index.php',
    $publicSource . '/router.php',
    $publicSource . '/.DS_Store',
]);

foreach ($appSourceDirectories as $directory) {
    copyDirectory($root . '/' . $directory, $appTarget . '/' . $directory, [
        $root . '/' . $directory . '/.DS_Store',
    ]);
}

copyStorage($storageSource, $appTarget . '/storage');

writeFile($publicTarget . '/index.php', publicIndexTemplate());
writeFile($publicTarget . '/router.php', publicRouterTemplate());

echo "Prepared FTP bundle in:\n";
echo "- {$publicTarget}\n";
echo "- {$appTarget}\n";

function publicIndexTemplate(): string
{
    return <<<'PHP'
<?php

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\SiteController;

$app = require dirname(__DIR__) . '/mka-app/src/bootstrap.php';

$router = new Router();

$site = new SiteController($app);
$meta = new MetaController($app);
$adminAuth = new AdminAuthController($app);
$adminDashboard = new AdminDashboardController($app);
$adminContent = new AdminContentController($app);
$adminMedia = new AdminMediaController($app);
$adminSettings = new AdminSettingsController($app);

$router->get('/', fn() => $site->root());
$router->get('/robots.txt', fn() => $meta->robots());
$router->get('/sitemap.xml', fn() => $meta->sitemap());
$router->get('/media/{path:.+}', fn(array $params) => $site->media($params['path']));

$router->get('/admin/login', fn() => $adminAuth->loginForm());
$router->post('/admin/login', fn() => $adminAuth->login());
$router->post('/admin/logout', fn() => $adminAuth->logout());

$router->get('/admin', fn() => $adminDashboard->index());

$router->get('/admin/content', fn() => $adminContent->index());
$router->get('/admin/content/new', fn() => $adminContent->create());
$router->get('/admin/content/{id:\d+}', fn(array $params) => $adminContent->edit((int) $params['id']));
$router->post('/admin/content/save', fn() => $adminContent->save());
$router->post('/admin/content/{id:\d+}/delete', fn(array $params) => $adminContent->delete((int) $params['id']));

$router->get('/admin/media', fn() => $adminMedia->index());
$router->get('/admin/media/new', fn() => $adminMedia->create());
$router->get('/admin/media/{id:\d+}', fn(array $params) => $adminMedia->edit((int) $params['id']));
$router->post('/admin/media/save', fn() => $adminMedia->save());
$router->post('/admin/media/{id:\d+}/delete', fn(array $params) => $adminMedia->delete((int) $params['id']));

$router->get('/admin/settings', fn() => $adminSettings->edit());
$router->post('/admin/settings/save', fn() => $adminSettings->save());

$router->get('/{locale:pl|en}', fn(array $params) => $site->home($params['locale']));
$router->get('/{locale:pl|en}/muzeum/{slug}', fn(array $params) => $site->sectionPage($params['locale'], 'MUZEUM', $params['slug']));
$router->get('/{locale:pl|en}/warsztat/{slug}', fn(array $params) => $site->sectionPage($params['locale'], 'WARSZTAT', $params['slug']));
$router->get('/{locale:pl|en}/edukacja/{slug}', fn(array $params) => $site->sectionPage($params['locale'], 'EDUKACJA', $params['slug']));
$router->get('/{locale:pl|en}/sklep', fn(array $params) => $site->singlePage($params['locale'], 'SHOP', 'sklep'));
$router->get('/{locale:pl|en}/wizyta', fn(array $params) => $site->singlePage($params['locale'], 'VISIT', 'wizyta'));
$router->get('/{locale:pl|en}/kontakt', fn(array $params) => $site->singlePage($params['locale'], 'CONTACT', 'kontakt'));
$router->get('/{locale:pl|en}/ksiazka-artystyczna', fn(array $params) => $site->singlePage($params['locale'], 'ART_BOOK', 'ksiazka-artystyczna'));
$router->get('/{locale:pl|en}/program/wystawy', fn(array $params) => $site->programListing($params['locale'], 'EXHIBITION', 'wystawy'));
$router->get('/{locale:pl|en}/program/wystawy/{slug}', fn(array $params) => $site->detail($params['locale'], 'EXHIBITION', $params['slug']));
$router->get('/{locale:pl|en}/program/wydarzenia', fn(array $params) => $site->programListing($params['locale'], 'EVENT', 'wydarzenia'));
$router->get('/{locale:pl|en}/program/wydarzenia/{slug}', fn(array $params) => $site->detail($params['locale'], 'EVENT', $params['slug']));
$router->get('/{locale:pl|en}/program/projekty', fn(array $params) => $site->programListing($params['locale'], 'PROJECT', 'projekty'));
$router->get('/{locale:pl|en}/program/projekty/{slug}', fn(array $params) => $site->detail($params['locale'], 'PROJECT', $params['slug']));
$router->get('/{locale:pl|en}/ksiazka-artystyczna/kolekcja', fn(array $params) => $site->collectionListing($params['locale']));
$router->get('/{locale:pl|en}/ksiazka-artystyczna/kolekcja/{slug}', fn(array $params) => $site->detail($params['locale'], 'COLLECTION', $params['slug']));
$router->get('/{locale:pl|en}/ksiazka-artystyczna/galeria', fn(array $params) => $site->galleryListing($params['locale']));
$router->get('/{locale:pl|en}/ksiazka-artystyczna/galeria/{slug}', fn(array $params) => $site->detail($params['locale'], 'GALLERY', $params['slug']));

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$dispatchMethod = $method === 'HEAD' ? 'GET' : $method;

$response = $router->dispatch($dispatchMethod, rtrim($path, '/') ?: '/');

if ($response === null) {
    http_response_code(404);
    echo $site->notFound();
    exit;
}

echo $response;
PHP;
}

function publicRouterTemplate(): string
{
    return <<<'PHP'
<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
PHP;
}

function recreateDirectory(string $directory): void
{
    if (is_dir($directory)) {
        removeDirectory($directory);
    }

    mkdir($directory, 0775, true);
}

function removeDirectory(string $directory): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($directory);
}

function copyDirectory(string $source, string $target, array $exclude = []): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();

        if (in_array($sourcePath, $exclude, true) || $item->getBasename() === '.DS_Store') {
            continue;
        }

        $relativePath = substr($sourcePath, strlen($source) + 1);
        $targetPath = $target . '/' . $relativePath;

        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0775, true);
            }

            continue;
        }

        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0775, true);
        }

        copy($sourcePath, $targetPath);
    }
}

function copyStorage(string $source, string $target): void
{
    recreateDirectory($target);
    mkdir($target . '/uploads', 0775, true);
    mkdir($target . '/database', 0775, true);

    copyDirectory($source . '/uploads', $target . '/uploads', [
        $source . '/uploads/.DS_Store',
    ]);

    $gitignore = $source . '/uploads/.gitignore';
    if (is_file($gitignore)) {
        copy($gitignore, $target . '/uploads/.gitignore');
    }
}

function writeFile(string $path, string $contents): void
{
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    file_put_contents($path, $contents);
}

<?php

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminChromeController;
use App\Http\Controllers\AdminDatabaseController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\AdminMenuController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminTypographyController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\SiteController;

$app = require dirname(__DIR__) . '/mka-app/src/bootstrap.php';

$router = new Router();

$site = new SiteController($app);
$meta = new MetaController($app);
$adminAuth = new AdminAuthController($app);
$adminDashboard = new AdminDashboardController($app);
$adminDatabase = new AdminDatabaseController($app);
$adminContent = new AdminContentController($app);
$adminChrome = new AdminChromeController($app);
$adminMedia = new AdminMediaController($app);
$adminMenu = new AdminMenuController($app);
$adminSettings = new AdminSettingsController($app);
$adminTypography = new AdminTypographyController($app);

$router->get('/', fn() => $site->root());
$router->get('/robots.txt', fn() => $meta->robots());
$router->get('/sitemap.xml', fn() => $meta->sitemap());
$router->get('/media/{path:.+}', fn(array $params) => $site->media($params['path']));

$router->get('/admin/login', fn() => $adminAuth->loginForm());
$router->post('/admin/login', fn() => $adminAuth->login());
$router->post('/admin/logout', fn() => $adminAuth->logout());

$router->get('/admin', fn() => $adminDashboard->index());
$router->get('/admin/locale/{locale:pl|en}', fn(array $params) => $adminDashboard->setLocale($params['locale']));
$router->get('/admin/database', fn() => $adminDatabase->index());
$router->post('/admin/database/migrate', fn() => $adminDatabase->migrate());

$router->get('/admin/content', fn() => $adminContent->index());
$router->get('/admin/content/new', fn() => $adminContent->create());
$router->get('/admin/content/{id:\d+}', fn(array $params) => $adminContent->edit((int) $params['id']));
$router->post('/admin/content/translate', fn() => $adminContent->translate());
$router->post('/admin/content/save', fn() => $adminContent->save());
$router->post('/admin/content/{id:\d+}/delete', fn(array $params) => $adminContent->delete((int) $params['id']));

$router->get('/admin/media', fn() => $adminMedia->index());
$router->get('/admin/media/new', fn() => $adminMedia->create());
$router->get('/admin/media/{id:\d+}', fn(array $params) => $adminMedia->edit((int) $params['id']));
$router->post('/admin/media/save', fn() => $adminMedia->save());
$router->post('/admin/media/{id:\d+}/delete', fn(array $params) => $adminMedia->delete((int) $params['id']));

$router->get('/admin/menu', fn() => $adminMenu->index());
$router->get('/admin/menu/{id:\d+}', fn(array $params) => $adminMenu->edit((int) $params['id']));
$router->post('/admin/menu/save', fn() => $adminMenu->save());
$router->post('/admin/menu/appearance/save', fn() => $adminMenu->saveAppearance());
$router->post('/admin/menu/import-current', fn() => $adminMenu->importCurrent());
$router->post('/admin/menu/reorder', fn() => $adminMenu->reorder());
$router->post('/admin/menu/{id:\d+}/visibility', fn(array $params) => $adminMenu->setVisibility((int) $params['id']));
$router->post('/admin/menu/{id:\d+}/up', fn(array $params) => $adminMenu->moveUp((int) $params['id']));
$router->post('/admin/menu/{id:\d+}/down', fn(array $params) => $adminMenu->moveDown((int) $params['id']));
$router->post('/admin/menu/{id:\d+}/delete', fn(array $params) => $adminMenu->delete((int) $params['id']));

$router->get('/admin/settings', fn() => $adminSettings->edit());
$router->get('/admin/settings/google/connect', fn() => $adminSettings->connectGoogle());
$router->get('/admin/settings/google/callback', fn() => $adminSettings->googleCallback());
$router->post('/admin/settings/google/disconnect', fn() => $adminSettings->disconnectGoogle());
$router->post('/admin/settings/translate', fn() => $adminSettings->translate());
$router->post('/admin/settings/save', fn() => $adminSettings->save());
$router->get('/admin/chrome', fn() => $adminChrome->edit());
$router->post('/admin/chrome/save', fn() => $adminChrome->save());
$router->get('/admin/typography', fn() => $adminTypography->edit());
$router->post('/admin/typography/save', fn() => $adminTypography->save());

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

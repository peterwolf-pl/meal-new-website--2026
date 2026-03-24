<?php

declare(strict_types=1);

use App\Core\App;

require_once __DIR__ . '/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

date_default_timezone_set('Europe/Warsaw');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$appClassPath = __DIR__ . '/Core/App.php';

if (!is_file($appClassPath)) {
    throw new \RuntimeException('Missing core application file: ' . $appClassPath);
}

require_once $appClassPath;

$config = require dirname(__DIR__) . '/config/app.php';
$localConfigPath = dirname(__DIR__) . '/config/local.php';

if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;

    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

return new App($config);

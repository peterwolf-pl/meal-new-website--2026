<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/mka-app/src/bootstrap.php';
$result = $app->seeder()->run();

echo $result . PHP_EOL;

<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/mka-app/src/bootstrap.php';
$executed = $app->migrator()->migrate();

if ($executed === []) {
    echo "No new migrations.\n";
    exit(0);
}

foreach ($executed as $migration) {
    echo "Applied: {$migration}\n";
}

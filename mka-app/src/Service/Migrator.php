<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\App;
use App\Core\Database;
use PDO;
use PDOException;

final class Migrator
{
    public function __construct(
        private readonly Database $database,
        private readonly App $app
    ) {
    }

    public function migrate(): array
    {
        $pdo = $this->database->pdo();
        $this->ensureMigrationsTable($pdo);

        $files = $this->migrationFiles();
        $applied = $this->appliedMigrations($pdo);
        $executed = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $sql = trim((string) file_get_contents($file));
            if ($sql === '') {
                continue;
            }

            try {
                $this->database->transaction(function (PDO $transactionPdo) use ($name, $sql): void {
                    $transactionPdo->exec($sql);
                    $statement = $transactionPdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
                    $statement->execute(['migration' => $name]);
                });
            } catch (PDOException $exception) {
                if (!$this->shouldMarkAsApplied($exception)) {
                    throw $exception;
                }

                $statement = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
                $statement->execute(['migration' => $name]);
            }

            $executed[] = $name;
        }

        return $executed;
    }

    public function status(): array
    {
        $pdo = $this->database->pdo();
        $this->ensureMigrationsTable($pdo);

        $files = $this->migrationFiles();
        $applied = $this->appliedMigrations($pdo);
        $pending = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $applied, true)) {
                $pending[] = $name;
            }
        }

        return [
            'driver' => $this->database->driver(),
            'applied' => $applied,
            'pending' => $pending,
            'all' => array_map('basename', $files),
        ];
    }

    private function ensureMigrationsTable(PDO $pdo): void
    {
        if ($this->database->driver() === 'mysql') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS migrations (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            return;
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private function migrationFiles(): array
    {
        $driver = $this->database->driver();
        $directory = $this->app->path('root') . '/database/migrations/' . $driver;
        $files = glob($directory . '/*.sql') ?: [];
        sort($files);

        return $files;
    }

    private function appliedMigrations(PDO $pdo): array
    {
        return $pdo->query('SELECT migration FROM migrations ORDER BY migration ASC')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function shouldMarkAsApplied(PDOException $exception): bool
    {
        if ($this->database->driver() !== 'mysql') {
            return false;
        }

        $message = strtolower($exception->getMessage());
        $patterns = [
            'duplicate column name',
            'column already exists',
            'duplicate key name',
            'already exists',
            'duplicate foreign key constraint name',
            'duplicate entry',
            'errno: 1060',
            'errno: 1061',
            'errno: 1826',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }
}

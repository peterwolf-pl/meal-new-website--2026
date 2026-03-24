<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly array $config)
    {
    }

    public function driver(): string
    {
        return (string) ($this->config['driver'] ?? 'sqlite');
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $driver = $this->driver();

        if ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'] ?? '127.0.0.1',
                $this->config['port'] ?? '3306',
                $this->config['database'] ?? '',
                $this->config['charset'] ?? 'utf8mb4'
            );

            $this->pdo = new PDO(
                $dsn,
                (string) ($this->config['username'] ?? ''),
                (string) ($this->config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true,
                ]
            );

            return $this->pdo;
        }

        $database = (string) ($this->config['database'] ?? '');
        $directory = dirname($database);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->pdo = new PDO(
            'sqlite:' . $database,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $this->pdo->exec('PRAGMA foreign_keys = ON');

        return $this->pdo;
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    public function tableExists(string $table): bool
    {
        $table = $this->normalizeIdentifier($table);
        $pdo = $this->pdo();

        if ($this->driver() === 'mysql') {
            $statement = $pdo->prepare(
                'SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = :table'
            );
            $statement->execute(['table' => $table]);

            return (int) $statement->fetchColumn() > 0;
        }

        $statement = $pdo->prepare(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table"
        );
        $statement->execute(['table' => $table]);

        return (bool) $statement->fetchColumn();
    }

    public function tableColumns(string $table): array
    {
        $table = $this->normalizeIdentifier($table);
        $pdo = $this->pdo();

        if ($this->driver() === 'mysql') {
            $statement = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
            $columns = [];

            foreach ($statement->fetchAll() ?: [] as $column) {
                $name = $column['Field'] ?? null;
                if (is_string($name) && $name !== '') {
                    $columns[] = $name;
                }
            }

            return $columns;
        }

        $statement = $pdo->query('PRAGMA table_info("' . $table . '")');
        $columns = [];

        foreach ($statement->fetchAll() ?: [] as $column) {
            $name = $column['name'] ?? null;
            if (is_string($name) && $name !== '') {
                $columns[] = $name;
            }
        }

        return $columns;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException('Invalid database identifier: ' . $identifier);
        }

        return $identifier;
    }
}

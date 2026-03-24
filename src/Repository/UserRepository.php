<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

final class UserRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => mb_strtolower(trim($email))]);

        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->database->pdo()->prepare('SELECT id, email, display_name, created_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function count(): int
    {
        return (int) $this->database->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function create(string $email, string $passwordHash, string $displayName): int
    {
        $statement = $this->database->pdo()->prepare(
            'INSERT INTO users (email, password_hash, display_name) VALUES (:email, :password_hash, :display_name)'
        );
        $statement->execute([
            'email' => mb_strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'display_name' => trim($displayName),
        ]);

        return $this->database->lastInsertId();
    }
}

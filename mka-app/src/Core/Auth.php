<?php

declare(strict_types=1);

namespace App\Core;

use App\Repository\UserRepository;

final class Auth
{
    private bool $resolvedUser = false;
    private ?array $currentUser = null;

    public function __construct(
        private readonly UserRepository $users,
        private readonly string $sessionKey
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[$this->sessionKey] = (int) $user['id'];

        return true;
    }

    public function user(): ?array
    {
        if ($this->resolvedUser) {
            return $this->currentUser;
        }

        $id = $_SESSION[$this->sessionKey] ?? null;

        $this->currentUser = $id ? $this->users->findById((int) $id) : null;
        $this->resolvedUser = true;

        return $this->currentUser;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function logout(): void
    {
        unset($_SESSION[$this->sessionKey]);
        $this->currentUser = null;
        $this->resolvedUser = true;
        session_regenerate_id(true);
    }
}

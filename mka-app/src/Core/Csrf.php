<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public function __construct(private readonly string $sessionKey)
    {
    }

    public function token(): string
    {
        if (empty($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[$this->sessionKey];
    }

    public function validate(?string $token): bool
    {
        $sessionToken = $_SESSION[$this->sessionKey] ?? '';

        return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}

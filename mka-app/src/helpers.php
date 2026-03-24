<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function selected(string $value, ?string $current): string
{
    return $value === $current ? 'selected' : '';
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

function is_blank(?string $value): bool
{
    return trim((string) $value) === '';
}

function array_pick(array $array, string $key, mixed $default = null): mixed
{
    return $array[$key] ?? $default;
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

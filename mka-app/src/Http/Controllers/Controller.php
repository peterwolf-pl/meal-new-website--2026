<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\App;

abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    protected function render(string $view, array $data = [], string $layout = 'public'): string
    {
        return $this->app->view()->render($view, $data, $layout);
    }

    protected function redirect(string $path): string
    {
        header('Location: ' . $path);

        return '';
    }

    protected function requireAdmin(): ?string
    {
        if ($this->app->auth()->check()) {
            return null;
        }

        $this->app->flash('error', 'Zaloguj się, aby przejść do panelu CMS.');

        return $this->redirect('/admin/login');
    }

    protected function validateCsrf(): bool
    {
        $token = $_POST['_token'] ?? null;

        if ($this->isValidCsrfToken($token)) {
            return true;
        }

        http_response_code(419);
        echo 'Nieprawidłowy token sesji.';

        return false;
    }

    protected function notFound(string $message = 'Nie znaleziono zasobu.'): string
    {
        http_response_code(404);

        return $message;
    }

    protected function isValidCsrfToken(mixed $token): bool
    {
        return $this->app->csrf()->validate(is_string($token) ? $token : null);
    }

    protected function json(array $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        return (string) json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    protected function adminLocale(): string
    {
        $locale = $_SESSION['admin_locale'] ?? $this->app->defaultLocale();

        return $this->app->localeOrDefault(is_string($locale) ? $locale : null);
    }

    protected function setAdminLocale(string $locale): void
    {
        $_SESSION['admin_locale'] = $this->app->localeOrDefault($locale);
    }

    protected function safeRedirectPath(?string $path, string $fallback = '/admin'): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return $fallback;
        }

        $parsedPath = parse_url($path, PHP_URL_PATH) ?: '';
        $parsedQuery = parse_url($path, PHP_URL_QUERY) ?: '';

        if ($parsedPath === '' || !str_starts_with($parsedPath, '/')) {
            return $fallback;
        }

        if (str_starts_with($parsedPath, '/admin/locale/')) {
            return $fallback;
        }

        return $parsedQuery !== '' ? $parsedPath . '?' . $parsedQuery : $parsedPath;
    }
}

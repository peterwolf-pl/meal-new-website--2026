<?php

declare(strict_types=1);

namespace App\Core;

use App\Repository\ContentRepository;
use App\Repository\MediaRepository;
use App\Repository\NavigationRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Service\ContentService;
use App\Service\GoogleIntegrationService;
use App\Service\Migrator;
use App\Service\Navigation;
use App\Service\OpenAiTranslationService;
use App\Service\Seeder;

final class App
{
    private array $services = [];

    public function __construct(private readonly array $config)
    {
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function path(string $key): string
    {
        return (string) $this->config('paths.' . $key);
    }

    public function baseUrl(string $path = ''): string
    {
        $base = rtrim((string) $this->config('base_url'), '/');

        if ($path === '') {
            return $base;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $base . '/' . ltrim($path, '/');
    }

    public function db(): Database
    {
        return $this->services['db'] ??= new Database($this->config('db'));
    }

    public function view(): View
    {
        return $this->services['view'] ??= new View($this);
    }

    public function csrf(): Csrf
    {
        return $this->services['csrf'] ??= new Csrf((string) $this->config('auth.csrf_key'));
    }

    public function auth(): Auth
    {
        return $this->services['auth'] ??= new Auth(
            $this->userRepository(),
            (string) $this->config('auth.session_key')
        );
    }

    public function contentRepository(): ContentRepository
    {
        return $this->services['content_repository'] ??= new ContentRepository($this->db(), $this);
    }

    public function mediaRepository(): MediaRepository
    {
        return $this->services['media_repository'] ??= new MediaRepository($this->db(), $this);
    }

    public function settingRepository(): SettingRepository
    {
        return $this->services['setting_repository'] ??= new SettingRepository($this->db(), $this);
    }

    public function navigationRepository(): NavigationRepository
    {
        return $this->services['navigation_repository'] ??= new NavigationRepository($this->db());
    }

    public function userRepository(): UserRepository
    {
        return $this->services['user_repository'] ??= new UserRepository($this->db());
    }

    public function navigation(): Navigation
    {
        return $this->services['navigation'] ??= new Navigation($this);
    }

    public function contentService(): ContentService
    {
        return $this->services['content_service'] ??= new ContentService($this);
    }

    public function googleIntegration(): GoogleIntegrationService
    {
        return $this->services['google_integration'] ??= new GoogleIntegrationService($this);
    }

    public function openAiTranslation(): OpenAiTranslationService
    {
        return $this->services['openai_translation'] ??= new OpenAiTranslationService($this);
    }

    public function migrator(): Migrator
    {
        return $this->services['migrator'] ??= new Migrator($this->db(), $this);
    }

    public function seeder(): Seeder
    {
        return $this->services['seeder'] ??= new Seeder($this);
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function pullFlashMessages(): array
    {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);

        return is_array($messages) ? $messages : [];
    }

    public function defaultLocale(): string
    {
        return (string) $this->config('default_locale', 'pl');
    }

    public function localeOrDefault(?string $locale): string
    {
        $locale = $locale ?: $this->defaultLocale();

        return in_array($locale, (array) $this->config('supported_locales', ['pl']), true)
            ? $locale
            : $this->defaultLocale();
    }
}

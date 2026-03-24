<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\App;
use RuntimeException;
use Throwable;

final class GoogleIntegrationService
{
    private const CACHE_TTL = 900;
    private const ANALYTICS_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
    private const SEARCH_CONSOLE_SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
    private const TAG_MANAGER_SCOPE = 'https://www.googleapis.com/auth/tagmanager.readonly';
    private const OAUTH_AUTHORIZE_URI = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const OAUTH_TOKEN_URI = 'https://oauth2.googleapis.com/token';

    public function __construct(private readonly App $app)
    {
    }

    public function trackingConfig(array $settings): array
    {
        $gtmContainerId = $this->settingOrConfig($settings, 'gtm_container_id');
        $ga4MeasurementId = $this->settingOrConfig($settings, 'ga4_measurement_id');

        $mode = 'none';
        $label = 'Brak';

        if ($gtmContainerId !== '') {
            $mode = 'gtm';
            $label = 'Google Tag Manager';
        } elseif ($ga4MeasurementId !== '') {
            $mode = 'ga4';
            $label = 'Google Analytics 4';
        }

        return [
            'mode' => $mode,
            'label' => $label,
            'gtm_container_id' => $gtmContainerId,
            'ga4_measurement_id' => $ga4MeasurementId,
        ];
    }

    public function oauthConnection(array $settings): array
    {
        $oauthConfig = $this->oauthConfig();
        $connectedAt = trim((string) ($settings['google_oauth_connected_at'] ?? ''));

        return [
            'client_configured' => $oauthConfig['configured'],
            'callback_url' => $oauthConfig['redirect_uri'],
            'connected' => trim((string) ($settings['google_oauth_refresh_token'] ?? '')) !== '',
            'email' => trim((string) ($settings['google_oauth_email'] ?? '')),
            'connected_at' => $connectedAt,
            'tracking' => $this->trackingConfig($settings),
            'ga4_property_id' => $this->normalizeGaPropertyId((string) ($settings['ga4_property_id'] ?? '')),
            'ga4_measurement_id' => trim((string) ($settings['ga4_measurement_id'] ?? '')),
            'search_console_property_url' => trim((string) ($settings['search_console_property_url'] ?? '')),
            'gtm_container_id' => trim((string) ($settings['gtm_container_id'] ?? '')),
            'service_account_path' => $this->settingOrConfig($settings, 'google_service_account_json_path'),
        ];
    }

    public function authorizationUrl(string $state): string
    {
        $oauthConfig = $this->oauthConfig();
        if (!$oauthConfig['configured']) {
            throw new RuntimeException('Brakuje konfiguracji GOOGLE_OAUTH_CLIENT_ID lub GOOGLE_OAUTH_CLIENT_SECRET.');
        }

        return self::OAUTH_AUTHORIZE_URI . '?' . http_build_query([
            'client_id' => $oauthConfig['client_id'],
            'redirect_uri' => $oauthConfig['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', [
                'openid',
                'email',
                self::ANALYTICS_SCOPE,
                self::SEARCH_CONSOLE_SCOPE,
                self::TAG_MANAGER_SCOPE,
            ]),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function completeOAuthAutoconfiguration(array $settings, string $authorizationCode): array
    {
        $tokenResponse = $this->exchangeAuthorizationCode($authorizationCode);
        $accessToken = trim((string) ($tokenResponse['access_token'] ?? ''));

        if ($accessToken === '') {
            throw new RuntimeException('Google nie zwrócił tokenu dostępu po zalogowaniu.');
        }

        $refreshToken = trim((string) ($tokenResponse['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            $refreshToken = trim((string) ($settings['google_oauth_refresh_token'] ?? ''));
        }

        if ($refreshToken === '') {
            throw new RuntimeException('Google nie zwrócił refresh tokena. Spróbuj ponownie po pełnym ekranie zgody OAuth.');
        }

        $email = $this->extractEmailFromIdToken((string) ($tokenResponse['id_token'] ?? ''));
        $detected = $this->autoDetectResources($settings, $accessToken);

        $persist = [
            'google_oauth_refresh_token' => $refreshToken,
            'google_oauth_email' => $email !== '' ? $email : trim((string) ($settings['google_oauth_email'] ?? '')),
            'google_oauth_connected_at' => date(DATE_ATOM),
        ];

        foreach (['gtm_container_id', 'ga4_measurement_id', 'ga4_property_id', 'search_console_property_url'] as $field) {
            if (($detected[$field] ?? '') !== '') {
                $persist[$field] = $detected[$field];
            }
        }

        return [
            'settings' => $persist,
            'detected' => $detected,
            'message' => $this->autoconfigurationMessage($persist, $detected),
        ];
    }

    public function dashboard(array $settings): array
    {
        $config = $this->apiConfig($settings);
        $period = $this->dashboardPeriod();
        $payload = [
            'tracking' => $this->trackingConfig($settings),
            'source' => [
                'mode' => 'none',
                'label' => 'Brak autoryzacji Google API',
                'email' => '',
            ],
            'period' => $period,
            'analytics' => [
                'enabled' => $config['ga4_property_id'] !== '',
                'connected' => false,
                'summary' => null,
                'top_pages' => [],
                'error' => null,
            ],
            'search_console' => [
                'enabled' => $config['search_console_property_url'] !== '',
                'connected' => false,
                'summary' => null,
                'top_queries' => [],
                'error' => null,
            ],
            'updated_at' => null,
            'from_cache' => false,
        ];

        if (!$payload['analytics']['enabled'] && !$payload['search_console']['enabled']) {
            return $payload;
        }

        if ($cached = $this->readCache($this->cacheKey($config, $period))) {
            return $cached;
        }

        $authorization = $this->dashboardAuthorization($config, $payload);
        $payload['source'] = $authorization['source'];

        if ($authorization['access_token'] === null) {
            if ($payload['analytics']['enabled']) {
                $payload['analytics']['error'] = $authorization['error'];
            }

            if ($payload['search_console']['enabled']) {
                $payload['search_console']['error'] = $authorization['error'];
            }

            return $payload;
        }

        $accessToken = $authorization['access_token'];

        if ($payload['analytics']['enabled']) {
            try {
                $payload['analytics']['summary'] = $this->fetchAnalyticsSummary(
                    $accessToken,
                    $config['ga4_property_id'],
                    $period['start'],
                    $period['end']
                );
                $payload['analytics']['top_pages'] = $this->fetchAnalyticsTopPages(
                    $accessToken,
                    $config['ga4_property_id'],
                    $period['start'],
                    $period['end']
                );
                $payload['analytics']['connected'] = true;
            } catch (Throwable $exception) {
                $payload['analytics']['error'] = 'Nie udało się pobrać danych GA4: ' . $exception->getMessage();
            }
        }

        if ($payload['search_console']['enabled']) {
            try {
                $payload['search_console']['summary'] = $this->fetchSearchConsoleSummary(
                    $accessToken,
                    $config['search_console_property_url'],
                    $period['start'],
                    $period['end']
                );
                $payload['search_console']['top_queries'] = $this->fetchSearchConsoleTopQueries(
                    $accessToken,
                    $config['search_console_property_url'],
                    $period['start'],
                    $period['end']
                );
                $payload['search_console']['connected'] = true;
            } catch (Throwable $exception) {
                $payload['search_console']['error'] = 'Nie udało się pobrać danych Search Console: ' . $exception->getMessage();
            }
        }

        $payload['updated_at'] = date(DATE_ATOM);
        $this->writeCache($this->cacheKey($config, $period), $payload);

        return $payload;
    }

    private function apiConfig(array $settings): array
    {
        return [
            'ga4_property_id' => $this->normalizeGaPropertyId($this->settingOrConfig($settings, 'ga4_property_id')),
            'search_console_property_url' => $this->settingOrConfig($settings, 'search_console_property_url'),
            'service_account_path' => $this->resolveServiceAccountPath(
                $this->settingOrConfig($settings, 'google_service_account_json_path')
            ),
            'oauth_refresh_token' => trim((string) ($settings['google_oauth_refresh_token'] ?? '')),
            'oauth_email' => trim((string) ($settings['google_oauth_email'] ?? '')),
            'oauth_connected_at' => trim((string) ($settings['google_oauth_connected_at'] ?? '')),
        ];
    }

    private function oauthConfig(): array
    {
        $clientId = trim((string) $this->app->config('integrations.google_oauth_client_id', ''));
        $clientSecret = trim((string) $this->app->config('integrations.google_oauth_client_secret', ''));

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->app->baseUrl('/admin/settings/google/callback'),
            'configured' => $clientId !== '' && $clientSecret !== '',
        ];
    }

    private function settingOrConfig(array $settings, string $field): string
    {
        $settingValue = trim((string) ($settings[$field] ?? ''));
        if ($settingValue !== '') {
            return $settingValue;
        }

        return trim((string) $this->app->config('integrations.' . $field, ''));
    }

    private function dashboardPeriod(): array
    {
        return [
            'start' => date('Y-m-d', strtotime('-27 days')),
            'end' => date('Y-m-d', strtotime('-1 day')),
            'label' => 'ostatnie 28 dni',
        ];
    }

    private function normalizeGaPropertyId(string $propertyId): string
    {
        $propertyId = trim($propertyId);

        if (str_starts_with($propertyId, 'properties/')) {
            $propertyId = substr($propertyId, strlen('properties/'));
        }

        return preg_replace('/\s+/', '', $propertyId) ?: '';
    }

    private function resolveServiceAccountPath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($this->app->path('root'), '/') . '/' . ltrim($path, '/');
    }

    private function exchangeAuthorizationCode(string $authorizationCode): array
    {
        $oauthConfig = $this->oauthConfig();
        if (!$oauthConfig['configured']) {
            throw new RuntimeException('Brakuje konfiguracji GOOGLE_OAUTH_CLIENT_ID lub GOOGLE_OAUTH_CLIENT_SECRET.');
        }

        return $this->postForm(self::OAUTH_TOKEN_URI, [
            'code' => $authorizationCode,
            'client_id' => $oauthConfig['client_id'],
            'client_secret' => $oauthConfig['client_secret'],
            'redirect_uri' => $oauthConfig['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);
    }

    private function refreshUserAccessToken(string $refreshToken): string
    {
        $oauthConfig = $this->oauthConfig();
        if (!$oauthConfig['configured']) {
            throw new RuntimeException('Brakuje konfiguracji GOOGLE_OAUTH_CLIENT_ID lub GOOGLE_OAUTH_CLIENT_SECRET.');
        }

        $response = $this->postForm(self::OAUTH_TOKEN_URI, [
            'client_id' => $oauthConfig['client_id'],
            'client_secret' => $oauthConfig['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (empty($response['access_token'])) {
            throw new RuntimeException('Google nie zwrócił tokenu dostępu dla zapisanego refresh tokena.');
        }

        return (string) $response['access_token'];
    }

    private function autoDetectResources(array $settings, string $accessToken): array
    {
        $context = $this->siteContext($settings);
        $detected = [
            'gtm_container_id' => '',
            'ga4_measurement_id' => '',
            'ga4_property_id' => '',
            'search_console_property_url' => '',
        ];

        try {
            $match = $this->detectAnalyticsProperty($accessToken, $context);
            if ($match !== null) {
                $detected['ga4_property_id'] = $match['ga4_property_id'];
                $detected['ga4_measurement_id'] = $match['ga4_measurement_id'];
            }
        } catch (Throwable) {
        }

        try {
            $match = $this->detectSearchConsoleProperty($accessToken, $context);
            if ($match !== null) {
                $detected['search_console_property_url'] = $match['search_console_property_url'];
            }
        } catch (Throwable) {
        }

        try {
            $match = $this->detectTagManagerContainer($accessToken, $context);
            if ($match !== null) {
                $detected['gtm_container_id'] = $match['gtm_container_id'];
            }
        } catch (Throwable) {
        }

        return $detected;
    }

    private function detectSearchConsoleProperty(string $accessToken, array $context): ?array
    {
        $response = $this->getJson(
            'https://www.googleapis.com/webmasters/v3/sites',
            $accessToken
        );

        $best = null;

        foreach ($response['siteEntry'] ?? [] as $siteEntry) {
            $siteUrl = trim((string) ($siteEntry['siteUrl'] ?? ''));
            if ($siteUrl === '') {
                continue;
            }

            $score = 0;

            if (str_starts_with($siteUrl, 'sc-domain:')) {
                $domain = $this->normalizeHost(substr($siteUrl, strlen('sc-domain:')));
                if ($domain !== '' && in_array($domain, $context['host_candidates'], true)) {
                    $score = 95;
                }
            } else {
                $normalized = $this->normalizeSiteUrl($siteUrl);
                if ($normalized === '') {
                    continue;
                }

                if ($normalized === $context['base_url']) {
                    $score = 100;
                } else {
                    $host = $this->normalizeHost((string) parse_url($normalized, PHP_URL_HOST));
                    if ($host !== '' && in_array($host, $context['host_candidates'], true)) {
                        $score = 85;
                    }
                }
            }

            if ($score <= 0) {
                continue;
            }

            if ($best === null || $score > $best['score']) {
                $best = [
                    'search_console_property_url' => $siteUrl,
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    private function detectAnalyticsProperty(string $accessToken, array $context): ?array
    {
        $best = null;
        $secondBestScore = 0;

        foreach ($this->listAnalyticsPropertySummaries($accessToken) as $propertySummary) {
            $propertyResource = trim((string) ($propertySummary['property'] ?? ''));
            $propertyId = $this->normalizeGaPropertyId($propertyResource);
            if ($propertyId === '') {
                continue;
            }

            $propertyNameScore = $this->nameScore(
                trim((string) ($propertySummary['displayName'] ?? '')),
                $context['name_candidates']
            );

            foreach ($this->fetchAnalyticsDataStreams($accessToken, $propertyId) as $stream) {
                $streamType = strtoupper(trim((string) ($stream['type'] ?? '')));
                $webStream = is_array($stream['webStreamData'] ?? null) ? $stream['webStreamData'] : [];

                if ($streamType !== 'WEB_DATA_STREAM' && $webStream === []) {
                    continue;
                }

                $measurementId = trim((string) ($webStream['measurementId'] ?? ''));
                if ($measurementId === '') {
                    continue;
                }

                $defaultUri = trim((string) ($webStream['defaultUri'] ?? ''));
                $hostScore = $this->urlMatchScore($defaultUri, $context);
                $streamNameScore = $this->nameScore(
                    trim((string) ($stream['displayName'] ?? '')),
                    $context['name_candidates']
                );
                $score = min(100, $hostScore + $propertyNameScore + $streamNameScore);

                $candidate = [
                    'ga4_property_id' => $propertyId,
                    'ga4_measurement_id' => $measurementId,
                    'score' => $score,
                    'host_score' => $hostScore,
                    'name_score' => $propertyNameScore + $streamNameScore,
                ];

                if ($best === null || $score > $best['score']) {
                    if ($best !== null) {
                        $secondBestScore = max($secondBestScore, (int) $best['score']);
                    }

                    $best = $candidate;
                } else {
                    $secondBestScore = max($secondBestScore, $score);
                }
            }
        }

        if ($best === null) {
            return null;
        }

        if ($best['host_score'] >= 80) {
            return $best;
        }

        if ($best['name_score'] >= 40 && ($best['score'] - $secondBestScore) >= 20) {
            return $best;
        }

        return null;
    }

    private function detectTagManagerContainer(string $accessToken, array $context): ?array
    {
        $accounts = $this->fetchTagManagerAccounts($accessToken);
        $best = null;
        $secondBestScore = 0;

        foreach ($accounts as $account) {
            $accountId = trim((string) ($account['accountId'] ?? ''));
            if ($accountId === '') {
                $path = trim((string) ($account['path'] ?? ''));
                $accountId = $this->extractTrailingId($path);
            }

            if ($accountId === '') {
                continue;
            }

            foreach ($this->fetchTagManagerContainers($accessToken, $accountId) as $container) {
                $publicId = trim((string) ($container['publicId'] ?? ''));
                if ($publicId === '') {
                    continue;
                }

                $hostScore = 0;
                foreach ($container['domainName'] ?? [] as $domainName) {
                    $host = $this->normalizeHost((string) $domainName);
                    if ($host === '') {
                        continue;
                    }

                    if (in_array($host, $context['host_candidates'], true)) {
                        $hostScore = max($hostScore, 95);
                    }
                }

                $nameScore = $this->nameScore(
                    trim((string) ($container['name'] ?? '')),
                    $context['name_candidates']
                );
                $score = min(100, $hostScore + $nameScore);
                $candidate = [
                    'gtm_container_id' => $publicId,
                    'score' => $score,
                    'host_score' => $hostScore,
                    'name_score' => $nameScore,
                ];

                if ($best === null || $score > $best['score']) {
                    if ($best !== null) {
                        $secondBestScore = max($secondBestScore, (int) $best['score']);
                    }

                    $best = $candidate;
                } else {
                    $secondBestScore = max($secondBestScore, $score);
                }
            }
        }

        if ($best === null) {
            return null;
        }

        if ($best['host_score'] >= 90) {
            return $best;
        }

        if ($best['name_score'] >= 40 && ($best['score'] - $secondBestScore) >= 20) {
            return $best;
        }

        return null;
    }

    private function siteContext(array $settings): array
    {
        $baseUrl = $this->normalizeSiteUrl($this->app->baseUrl('/'));
        $host = $this->normalizeHost((string) parse_url($baseUrl, PHP_URL_HOST));

        return [
            'base_url' => $baseUrl,
            'host' => $host,
            'host_candidates' => $this->hostCandidates($host),
            'name_candidates' => $this->nameCandidates($settings),
        ];
    }

    private function hostCandidates(string $host): array
    {
        if ($host === '') {
            return [];
        }

        $candidates = [$host];
        if (str_starts_with($host, 'www.')) {
            $candidates[] = substr($host, 4);
        } else {
            $candidates[] = 'www.' . $host;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function nameCandidates(array $settings): array
    {
        $translations = is_array($settings['translations'] ?? null) ? $settings['translations'] : [];
        $candidates = [
            (string) $this->app->config('app_name', ''),
            (string) (($translations['pl']['museum_name'] ?? '')),
            (string) (($translations['en']['museum_name'] ?? '')),
        ];

        $normalized = [];
        foreach ($candidates as $candidate) {
            $value = $this->normalizeText($candidate);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeHost(string $host): string
    {
        return strtolower(trim($host, " \t\n\r\0\x0B./"));
    }

    private function normalizeSiteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = $this->normalizeHost((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return '';
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $path = '/' . trim((string) ($parts['path'] ?? '/'), '/');
        if ($path === '//') {
            $path = '/';
        }

        if ($path !== '/') {
            $path .= '/';
        }

        $portFragment = '';
        if ($port !== null && !in_array([$scheme, $port], [['http', 80], ['https', 443]], true)) {
            $portFragment = ':' . $port;
        }

        return $scheme . '://' . $host . $portFragment . $path;
    }

    private function urlMatchScore(string $candidateUrl, array $context): int
    {
        $normalizedUrl = $this->normalizeSiteUrl($candidateUrl);
        if ($normalizedUrl === '') {
            return 0;
        }

        if ($normalizedUrl === $context['base_url']) {
            return 100;
        }

        $host = $this->normalizeHost((string) parse_url($normalizedUrl, PHP_URL_HOST));

        return in_array($host, $context['host_candidates'], true) ? 85 : 0;
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';

        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }

    private function nameScore(string $candidate, array $siteNames): int
    {
        $candidate = $this->normalizeText($candidate);
        if ($candidate === '' || $siteNames === []) {
            return 0;
        }

        $score = 0;

        foreach ($siteNames as $siteName) {
            if ($siteName === '') {
                continue;
            }

            if ($candidate === $siteName) {
                return 50;
            }

            if (str_contains($candidate, $siteName) || str_contains($siteName, $candidate)) {
                $score = max($score, 35);
                continue;
            }

            foreach (explode(' ', $siteName) as $token) {
                if (strlen($token) < 5) {
                    continue;
                }

                if (str_contains($candidate, $token)) {
                    $score = max($score, 20);
                }
            }
        }

        return $score;
    }

    private function autoconfigurationMessage(array $persist, array $detected): string
    {
        $parts = [];
        $email = trim((string) ($persist['google_oauth_email'] ?? ''));

        if ($email !== '') {
            $parts[] = 'Połączono konto Google: ' . $email . '.';
        } else {
            $parts[] = 'Połączono konto Google.';
        }

        $configured = [];

        if (($detected['ga4_property_id'] ?? '') !== '') {
            $configured[] = 'GA4 property ' . $detected['ga4_property_id'];
        }

        if (($detected['ga4_measurement_id'] ?? '') !== '') {
            $configured[] = 'measurement ' . $detected['ga4_measurement_id'];
        }

        if (($detected['search_console_property_url'] ?? '') !== '') {
            $configured[] = 'Search Console ' . $detected['search_console_property_url'];
        }

        if (($detected['gtm_container_id'] ?? '') !== '') {
            $configured[] = 'GTM ' . $detected['gtm_container_id'];
        }

        if ($configured === []) {
            $parts[] = 'Połączenie zapisano, ale nie udało się jednoznacznie dopasować zasobów do tej domeny. Uzupełnij pola ręcznie.';
        } else {
            $parts[] = 'Auto-konfiguracja wykryła: ' . implode(', ', $configured) . '.';
        }

        return implode(' ', $parts);
    }

    private function dashboardAuthorization(array $config, array $payload): array
    {
        $scopes = array_values(array_filter([
            $payload['analytics']['enabled'] ? self::ANALYTICS_SCOPE : null,
            $payload['search_console']['enabled'] ? self::SEARCH_CONSOLE_SCOPE : null,
        ]));

        $oauthError = null;

        if ($config['oauth_refresh_token'] !== '') {
            try {
                return [
                    'access_token' => $this->refreshUserAccessToken($config['oauth_refresh_token']),
                    'source' => [
                        'mode' => 'oauth',
                        'label' => 'Połączenie Google OAuth',
                        'email' => $config['oauth_email'],
                    ],
                    'error' => null,
                ];
            } catch (Throwable $exception) {
                $oauthError = 'Zapisane połączenie Google OAuth nie działa: ' . $exception->getMessage();
            }
        }

        if ($config['service_account_path'] !== '') {
            try {
                return [
                    'access_token' => $this->fetchAccessToken($config['service_account_path'], $scopes),
                    'source' => [
                        'mode' => 'service_account',
                        'label' => 'Konto serwisowe Google',
                        'email' => '',
                    ],
                    'error' => null,
                ];
            } catch (Throwable $exception) {
                $serviceAccountError = 'Błąd autoryzacji Google API: ' . $exception->getMessage();

                return [
                    'access_token' => null,
                    'source' => [
                        'mode' => 'none',
                        'label' => 'Brak autoryzacji Google API',
                        'email' => '',
                    ],
                    'error' => $oauthError !== null ? $oauthError . ' Następnie nie udało się użyć konta serwisowego: ' . $exception->getMessage() : $serviceAccountError,
                ];
            }
        }

        return [
            'access_token' => null,
            'source' => [
                'mode' => 'none',
                'label' => 'Brak autoryzacji Google API',
                'email' => '',
            ],
            'error' => $oauthError ?? 'Połącz konto Google jednym kliknięciem albo podaj ścieżkę do pliku JSON konta serwisowego.',
        ];
    }

    private function fetchAccessToken(string $serviceAccountPath, array $scopes): string
    {
        if (!function_exists('openssl_sign')) {
            throw new RuntimeException('Brakuje rozszerzenia OpenSSL w PHP.');
        }

        $credentials = $this->loadServiceAccountCredentials($serviceAccountPath);
        $tokenUri = (string) ($credentials['token_uri'] ?? self::OAUTH_TOKEN_URI);
        $issuedAt = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        if (!empty($credentials['private_key_id'])) {
            $header['kid'] = (string) $credentials['private_key_id'];
        }

        $claims = [
            'iss' => (string) $credentials['client_email'],
            'scope' => implode(' ', $scopes),
            'aud' => $tokenUri,
            'exp' => $issuedAt + 3600,
            'iat' => $issuedAt,
        ];

        $segments = [
            $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode((string) json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);

        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('Nie udało się wczytać klucza prywatnego konta serwisowego.');
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Nie udało się podpisać żądania JWT.');
        }

        $assertion = $signingInput . '.' . $this->base64UrlEncode($signature);
        $response = $this->postForm($tokenUri, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        if (empty($response['access_token'])) {
            throw new RuntimeException('Google nie zwrócił tokenu dostępu.');
        }

        return (string) $response['access_token'];
    }

    private function fetchAnalyticsSummary(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        $response = $this->postJson(
            sprintf('https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport', rawurlencode($propertyId)),
            [
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'sessions'],
                    ['name' => 'screenPageViews'],
                ],
            ],
            $accessToken
        );

        $metricValues = $response['rows'][0]['metricValues'] ?? [];

        return [
            'active_users' => (int) (($metricValues[0]['value'] ?? '0')),
            'sessions' => (int) (($metricValues[1]['value'] ?? '0')),
            'screen_page_views' => (int) (($metricValues[2]['value'] ?? '0')),
        ];
    }

    private function fetchAnalyticsTopPages(string $accessToken, string $propertyId, string $startDate, string $endDate): array
    {
        $response = $this->postJson(
            sprintf('https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport', rawurlencode($propertyId)),
            [
                'dateRanges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'dimensions' => [
                    ['name' => 'pagePath'],
                ],
                'metrics' => [
                    ['name' => 'screenPageViews'],
                    ['name' => 'activeUsers'],
                ],
                'orderBys' => [
                    [
                        'metric' => ['metricName' => 'screenPageViews'],
                        'desc' => true,
                    ],
                ],
                'limit' => 10,
            ],
            $accessToken
        );

        $items = [];

        foreach ($response['rows'] ?? [] as $row) {
            $items[] = [
                'page_path' => (string) ($row['dimensionValues'][0]['value'] ?? ''),
                'screen_page_views' => (int) (($row['metricValues'][0]['value'] ?? '0')),
                'active_users' => (int) (($row['metricValues'][1]['value'] ?? '0')),
            ];
        }

        return $items;
    }

    private function fetchSearchConsoleSummary(string $accessToken, string $propertyUrl, string $startDate, string $endDate): array
    {
        $response = $this->postJson(
            sprintf(
                'https://www.googleapis.com/webmasters/v3/sites/%s/searchAnalytics/query',
                rawurlencode($propertyUrl)
            ),
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'rowLimit' => 1,
                'type' => 'web',
            ],
            $accessToken
        );

        $row = $response['rows'][0] ?? [];

        return [
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => (float) ($row['ctr'] ?? 0.0),
            'position' => (float) ($row['position'] ?? 0.0),
        ];
    }

    private function fetchSearchConsoleTopQueries(string $accessToken, string $propertyUrl, string $startDate, string $endDate): array
    {
        $response = $this->postJson(
            sprintf(
                'https://www.googleapis.com/webmasters/v3/sites/%s/searchAnalytics/query',
                rawurlencode($propertyUrl)
            ),
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => ['query'],
                'rowLimit' => 10,
                'type' => 'web',
            ],
            $accessToken
        );

        $items = [];

        foreach ($response['rows'] ?? [] as $row) {
            $items[] = [
                'query' => (string) ($row['keys'][0] ?? ''),
                'clicks' => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr' => (float) ($row['ctr'] ?? 0.0),
                'position' => (float) ($row['position'] ?? 0.0),
            ];
        }

        return $items;
    }

    private function listAnalyticsPropertySummaries(string $accessToken): array
    {
        $items = [];
        $pageToken = null;

        do {
            $query = ['pageSize' => 200];
            if ($pageToken !== null && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->getJson(
                'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986),
                $accessToken
            );

            foreach ($response['accountSummaries'] ?? [] as $accountSummary) {
                foreach ($accountSummary['propertySummaries'] ?? [] as $propertySummary) {
                    $items[] = $propertySummary;
                }
            }

            $pageToken = trim((string) ($response['nextPageToken'] ?? ''));
        } while ($pageToken !== '');

        return $items;
    }

    private function fetchAnalyticsDataStreams(string $accessToken, string $propertyId): array
    {
        $items = [];
        $pageToken = null;

        do {
            $query = ['pageSize' => 200];
            if ($pageToken !== null && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->getJson(
                'https://analyticsadmin.googleapis.com/v1beta/properties/' . rawurlencode($propertyId) . '/dataStreams?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986),
                $accessToken
            );

            foreach ($response['dataStreams'] ?? [] as $stream) {
                $items[] = $stream;
            }

            $pageToken = trim((string) ($response['nextPageToken'] ?? ''));
        } while ($pageToken !== '');

        return $items;
    }

    private function fetchTagManagerAccounts(string $accessToken): array
    {
        $response = $this->getJson(
            'https://tagmanager.googleapis.com/tagmanager/v2/accounts',
            $accessToken
        );

        return is_array($response['account'] ?? null) ? $response['account'] : [];
    }

    private function fetchTagManagerContainers(string $accessToken, string $accountId): array
    {
        $response = $this->getJson(
            'https://tagmanager.googleapis.com/tagmanager/v2/accounts/' . rawurlencode($accountId) . '/containers',
            $accessToken
        );

        return is_array($response['container'] ?? null) ? $response['container'] : [];
    }

    private function extractTrailingId(string $resource): string
    {
        $resource = trim($resource, '/');
        if ($resource === '' || !str_contains($resource, '/')) {
            return '';
        }

        return trim((string) substr($resource, (int) strrpos($resource, '/') + 1));
    }

    private function extractEmailFromIdToken(string $idToken): string
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            return '';
        }

        $segments = explode('.', $idToken);
        if (count($segments) < 2) {
            return '';
        }

        $decodedPayload = $this->base64UrlDecode($segments[1]);
        if ($decodedPayload === '') {
            return '';
        }

        try {
            $payload = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return '';
        }

        return trim((string) ($payload['email'] ?? ''));
    }

    private function loadServiceAccountCredentials(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Nie znaleziono pliku JSON konta serwisowego: ' . $path);
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('Plik JSON konta serwisowego jest pusty lub nieczytelny.');
        }

        $credentials = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        foreach (['client_email', 'private_key'] as $field) {
            if (empty($credentials[$field])) {
                throw new RuntimeException('Brakuje pola `' . $field . '` w pliku konta serwisowego.');
            }
        }

        return $credentials;
    }

    private function postForm(string $url, array $payload): array
    {
        return $this->decodeJson(
            $this->httpRequest(
                'POST',
                $url,
                ['Content-Type: application/x-www-form-urlencoded'],
                http_build_query($payload, '', '&', PHP_QUERY_RFC3986)
            ),
            $url
        );
    }

    private function postJson(string $url, array $payload, string $accessToken): array
    {
        return $this->decodeJson(
            $this->httpRequest(
                'POST',
                $url,
                [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ],
                (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            ),
            $url
        );
    }

    private function getJson(string $url, string $accessToken): array
    {
        return $this->decodeJson(
            $this->httpRequest(
                'GET',
                $url,
                ['Authorization: Bearer ' . $accessToken]
            ),
            $url
        );
    }

    private function httpRequest(string $method, string $url, array $headers, ?string $body = null): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Brakuje rozszerzenia cURL w PHP.');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Nie udało się zainicjalizować połączenia HTTP.');
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);

        if ($response === false) {
            throw new RuntimeException('Błąd połączenia HTTP: ' . $curlError);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException($this->formatHttpError($response, $httpCode));
        }

        return (string) $response;
    }

    private function decodeJson(string $payload, string $url): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('Nie udało się odczytać odpowiedzi JSON z ' . $url . '.');
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function formatHttpError(string $body, int $httpCode): string
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return 'HTTP ' . $httpCode . ': ' . trim($body);
        }

        $message = $decoded['error']['message'] ?? $decoded['message'] ?? null;

        return $message
            ? 'HTTP ' . $httpCode . ': ' . $message
            : 'HTTP ' . $httpCode . ': nieznany błąd Google API.';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private function cacheKey(array $config, array $period): string
    {
        $serviceAccountFingerprint = '';

        if ($config['service_account_path'] !== '' && is_file($config['service_account_path'])) {
            $serviceAccountFingerprint = (string) @filemtime($config['service_account_path']);
        }

        return sha1((string) json_encode([
            'ga4_property_id' => $config['ga4_property_id'],
            'search_console_property_url' => $config['search_console_property_url'],
            'service_account_path' => $config['service_account_path'],
            'service_account_fingerprint' => $serviceAccountFingerprint,
            'oauth_refresh_token' => $config['oauth_refresh_token'] !== '' ? sha1($config['oauth_refresh_token']) : '',
            'oauth_connected_at' => $config['oauth_connected_at'],
            'period' => $period,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function readCache(string $cacheKey): ?array
    {
        $path = $this->cachePath($cacheKey);

        if (!is_file($path) || (time() - filemtime($path)) > self::CACHE_TTL) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $decoded['from_cache'] = true;

        return $decoded;
    }

    private function writeCache(string $cacheKey, array $payload): void
    {
        $directory = dirname($this->cachePath($cacheKey));

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload['from_cache'] = false;
        file_put_contents(
            $this->cachePath($cacheKey),
            (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    private function cachePath(string $cacheKey): string
    {
        return rtrim($this->app->path('root'), '/') . '/storage/cache/google-dashboard-' . $cacheKey . '.json';
    }
}

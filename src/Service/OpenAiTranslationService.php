<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\App;
use RuntimeException;
use Throwable;

final class OpenAiTranslationService
{
    private const API_URL = 'https://api.openai.com/v1/responses';

    public function __construct(private readonly App $app)
    {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function configuredModel(): string
    {
        return trim((string) $this->app->config('integrations.openai_translation_model', 'gpt-4o-mini'));
    }

    public function translateContentToEnglish(array $source, array $context = []): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Brakuje klucza OpenAI API.');
        }

        $response = $this->postJson(self::API_URL, $this->requestPayload($source, $context));
        $translated = $this->decodeStructuredTranslation($response);

        return $this->normalizeTranslation($translated, $source);
    }

    public function translateSettingsToEnglish(array $source): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Brakuje klucza OpenAI API.');
        }

        $response = $this->postJson(self::API_URL, [
            'model' => $this->configuredModel(),
            'input' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You translate Polish CMS settings for a museum website into natural English.',
                        'Return only the JSON object required by the schema.',
                        'Translate every non-empty field and keep empty fields as empty strings.',
                        'For homepage_intro, preserve the HTML structure, tags, and formatting exactly.',
                        'Translate only human-readable HTML content.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => (string) json_encode([
                        'source_locale' => 'pl',
                        'target_locale' => 'en',
                        'source' => $source,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'english_settings_translation',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'museum_name' => ['type' => 'string'],
                            'opening_hours' => ['type' => 'string'],
                            'organization_description' => ['type' => 'string'],
                            'homepage_title' => ['type' => 'string'],
                            'homepage_lead' => ['type' => 'string'],
                            'homepage_intro' => ['type' => 'string'],
                            'visit_note' => ['type' => 'string'],
                            'default_seo_title' => ['type' => 'string'],
                            'default_meta_description' => ['type' => 'string'],
                            'default_og_title' => ['type' => 'string'],
                            'default_og_description' => ['type' => 'string'],
                        ],
                        'required' => [
                            'museum_name',
                            'opening_hours',
                            'organization_description',
                            'homepage_title',
                            'homepage_lead',
                            'homepage_intro',
                            'visit_note',
                            'default_seo_title',
                            'default_meta_description',
                            'default_og_title',
                            'default_og_description',
                        ],
                        'additionalProperties' => false,
                    ],
                    'strict' => true,
                ],
            ],
        ]);

        $translated = $this->decodeStructuredTranslation($response);

        return $this->normalizeSettingsTranslation($translated, $source);
    }

    private function requestPayload(array $source, array $context): array
    {
        return [
            'model' => $this->configuredModel(),
            'input' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $this->userPrompt($source, $context),
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'english_content_translation',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'slug' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'body' => ['type' => 'string'],
                            'employee_projects' => ['type' => 'string'],
                            'seo_keywords' => ['type' => 'string'],
                            'seo_title' => ['type' => 'string'],
                            'meta_description' => ['type' => 'string'],
                            'og_title' => ['type' => 'string'],
                            'og_description' => ['type' => 'string'],
                        ],
                        'required' => [
                            'slug',
                            'title',
                            'summary',
                            'body',
                            'employee_projects',
                            'seo_keywords',
                            'seo_title',
                            'meta_description',
                            'og_title',
                            'og_description',
                        ],
                        'additionalProperties' => false,
                    ],
                    'strict' => true,
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'You translate Polish CMS content for a museum website into natural English.',
            'Return only the JSON object required by the schema.',
            'Translate every non-empty field and keep empty fields as empty strings.',
            'Preserve proper nouns, artwork titles, personal names, email addresses, and URLs unless they clearly require translation.',
            'For the slug, return a concise English URL slug using lowercase latin letters, digits, and hyphens only.',
            'For seo_keywords, keep a comma-separated list in English.',
            'For the body field, preserve the HTML structure, tag order, and formatting exactly.',
            'Translate only human-readable content inside the HTML, including figcaptions and alt text, without adding or removing elements.',
        ]);
    }

    private function userPrompt(array $source, array $context): string
    {
        return (string) json_encode([
            'context' => [
                'content_type' => (string) ($context['content_type'] ?? 'PAGE'),
                'section_key' => (string) ($context['section_key'] ?? 'MUZEUM'),
            ],
            'source_locale' => 'pl',
            'target_locale' => 'en',
            'source' => $source,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function decodeStructuredTranslation(array $response): array
    {
        $text = $this->extractOutputText($response);

        if ($text === '') {
            throw new RuntimeException('OpenAI nie zwrócił treści tłumaczenia.');
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('Nie udało się odczytać odpowiedzi tłumaczenia jako JSON.');
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function extractOutputText(array $response): string
    {
        $chunks = [];

        foreach ($response['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    $chunks[] = (string) $content['text'];
                }
            }
        }

        return trim(implode('', $chunks));
    }

    private function normalizeTranslation(array $translated, array $source): array
    {
        $fields = [
            'slug',
            'title',
            'summary',
            'body',
            'employee_projects',
            'seo_keywords',
            'seo_title',
            'meta_description',
            'og_title',
            'og_description',
        ];

        $normalized = [];

        foreach ($fields as $field) {
            $normalized[$field] = trim((string) ($translated[$field] ?? ''));

            if (trim(strip_tags((string) ($source[$field] ?? ''))) === '') {
                $normalized[$field] = '';
            }
        }

        if ($normalized['slug'] === '' && $normalized['title'] !== '') {
            $normalized['slug'] = $normalized['title'];
        }

        $normalized['slug'] = $this->app->contentService()->normalizeSlug($normalized['slug']);
        $normalized['summary'] = trim(strip_tags($normalized['summary']));
        $normalized['employee_projects'] = trim(strip_tags($normalized['employee_projects']));
        $normalized['meta_description'] = trim(strip_tags($normalized['meta_description']));
        $normalized['body'] = $this->app->contentService()->sanitizeRichText($normalized['body']);

        return $normalized;
    }

    private function normalizeSettingsTranslation(array $translated, array $source): array
    {
        $fields = [
            'museum_name',
            'opening_hours',
            'organization_description',
            'homepage_title',
            'homepage_lead',
            'homepage_intro',
            'visit_note',
            'default_seo_title',
            'default_meta_description',
            'default_og_title',
            'default_og_description',
        ];

        $normalized = [];

        foreach ($fields as $field) {
            $normalized[$field] = trim((string) ($translated[$field] ?? ''));

            if (trim(strip_tags((string) ($source[$field] ?? ''))) === '') {
                $normalized[$field] = '';
            }
        }

        $normalized['opening_hours'] = trim(strip_tags($normalized['opening_hours']));
        $normalized['organization_description'] = trim(strip_tags($normalized['organization_description']));
        $normalized['homepage_lead'] = trim(strip_tags($normalized['homepage_lead']));
        $normalized['homepage_intro'] = $this->app->contentService()->sanitizeRichText($normalized['homepage_intro']);
        $normalized['visit_note'] = trim(strip_tags($normalized['visit_note']));
        $normalized['default_meta_description'] = trim(strip_tags($normalized['default_meta_description']));
        $normalized['default_og_description'] = trim(strip_tags($normalized['default_og_description']));

        return $normalized;
    }

    private function postJson(string $url, array $payload): array
    {
        return $this->decodeJson(
            $this->httpRequest(
                'POST',
                $url,
                [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey(),
                ],
                (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
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
            CURLOPT_TIMEOUT => 45,
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
            throw new RuntimeException($this->formatHttpError((string) $response, $httpCode));
        }

        return (string) $response;
    }

    private function decodeJson(string $payload, string $url): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
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
            : 'HTTP ' . $httpCode . ': nieznany błąd OpenAI API.';
    }

    private function apiKey(): string
    {
        return trim((string) $this->app->config('integrations.openai_api_key', ''));
    }
}

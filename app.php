<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dbDriver = (string) env_value('DB_DRIVER', 'mysql');
$defaultDatabase = $dbDriver === 'sqlite'
    ? $root . '/storage/database/app.sqlite'
    : 'srv51934_mkalplnew';

return [
    'app_name' => 'Muzeum Książki Artystycznej',
    'base_url' => rtrim((string) env_value('APP_BASE_URL', 'https://mkal.pl'), '/'),
    'default_locale' => 'pl',
    'supported_locales' => ['pl', 'en'],
    'paths' => [
        'root' => $root,
        'views' => $root . '/views',
        'uploads' => dirname($root) . '/upload',
        'sqlite_database' => $root . '/storage/database/app.sqlite',
    ],
    'auth' => [
        'session_key' => 'mka_admin_user_id',
        'csrf_key' => 'mka_csrf_token',
    ],
    'theme' => [
        'font_presets' => [
            'editorial-sans' => [
                'label' => 'Editorial Sans',
                'stack' => '"Avenir Next", "Gill Sans", "Trebuchet MS", sans-serif',
            ],
            'library-serif' => [
                'label' => 'Library Serif',
                'stack' => '"Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif',
            ],
            'technical-sans' => [
                'label' => 'Technical Sans',
                'stack' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
            ],
            'reading-serif' => [
                'label' => 'Reading Serif',
                'stack' => 'Georgia, "Times New Roman", serif',
            ],
            'monospace-editorial' => [
                'label' => 'Monospace Editorial',
                'stack' => '"IBM Plex Mono", "Courier New", Courier, monospace',
            ],
        ],
    ],
    'integrations' => [
        'tinymce_api_key' => (string) env_value('TINYMCE_API_KEY', 'xxx'),
        'gtm_container_id' => (string) env_value('GTM_CONTAINER_ID', ''),
        'ga4_measurement_id' => (string) env_value('GA4_MEASUREMENT_ID', ''),
        'ga4_property_id' => (string) env_value('GA4_PROPERTY_ID', ''),
        'search_console_property_url' => (string) env_value('SEARCH_CONSOLE_PROPERTY_URL', ''),
        'google_service_account_json_path' => (string) env_value('GOOGLE_SERVICE_ACCOUNT_JSON_PATH', ''),
        'google_oauth_client_id' => (string) env_value('GOOGLE_OAUTH_CLIENT_ID', ''),
        'google_oauth_client_secret' => (string) env_value('GOOGLE_OAUTH_CLIENT_SECRET', ''),
        'openai_api_key' => (string) env_value('OPENAI_API_KEY', ''),
        'openai_translation_model' => (string) env_value('OPENAI_TRANSLATION_MODEL', 'gpt-4o-mini'),
    ],
    'db' => [
        'driver' => $dbDriver,
        'host' => (string) env_value('DB_HOST', 'localhost'),
        'port' => (string) env_value('DB_PORT', '3306'),
        'database' => (string) env_value('DB_DATABASE', $defaultDatabase),
        'username' => (string) env_value('DB_USERNAME', 'xxx'),
        'password' => (string) env_value('DB_PASSWORD', 'xxx'),
        'charset' => (string) env_value('DB_CHARSET', 'utf8mb4'),
    ],
];

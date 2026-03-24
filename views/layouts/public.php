<!DOCTYPE html>
<html lang="<?= e($locale ?? 'pl') ?>">
<head>
    <?php
    $cssVersion = @filemtime($app->path('root') . '/../public_html/css/site.css') ?: time();
    $jsVersion = @filemtime($app->path('root') . '/../public_html/js/site.js') ?: time();
    $trackingConfig = $app->googleIntegration()->trackingConfig($settings ?? []);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($seo['title'] ?? $pageTitle ?? 'Muzeum Książki Artystycznej') ?></title>
    <meta name="description" content="<?= e($seo['description'] ?? '') ?>">
    <meta property="og:title" content="<?= e($seo['og_title'] ?? ($seo['title'] ?? '')) ?>">
    <meta property="og:description" content="<?= e($seo['og_description'] ?? ($seo['description'] ?? '')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($seo['canonical'] ?? '') ?>">
    <?php if (!empty($seo['image'])): ?>
        <meta property="og:image" content="<?= e(str_starts_with($seo['image'], 'http') ? $seo['image'] : $app->baseUrl($seo['image'])) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= e($seo['canonical'] ?? '') ?>">
    <?php
    $fontRoles = [
        '--font-body-stack' => [
            'preset' => $settings['body_font_preset'] ?? 'editorial-sans',
            'asset' => $settings['body_font_media'] ?? null,
        ],
        '--font-heading-stack' => [
            'preset' => $settings['heading_font_preset'] ?? 'editorial-sans',
            'asset' => $settings['heading_font_media'] ?? null,
        ],
        '--font-menu-stack' => [
            'preset' => $settings['menu_font_preset'] ?? 'editorial-sans',
            'asset' => $settings['menu_font_media'] ?? null,
        ],
    ];
    $fontFaces = [];
    $fontVariables = [];
    $textTransform = static function (bool $capitalize, bool $uppercase): string {
        if ($uppercase) {
            return 'uppercase';
        }

        if ($capitalize) {
            return 'capitalize';
        }

        return 'none';
    };

    foreach ($fontRoles as $variable => $role) {
        $stack = $app->contentService()->fontPresetStack($role['preset']);
        $fontVariables[$variable] = $stack;
        $asset = $role['asset'];

        if (!is_array($asset) || ($asset['kind'] ?? '') !== 'font' || empty($asset['public_url'])) {
            continue;
        }

        $family = $app->contentService()->fontFaceFamily($asset);
        $fontFaces[$family] = [
            'family' => $family,
            'url' => $asset['public_url'],
            'format' => $app->contentService()->fontFaceFormat($asset),
        ];
        $fontVariables[$variable] = '"' . $family . '", ' . $stack;
    }
    $fontVariables += [
        '--font-body-size' => $settings['body_font_size'] ?? '1rem',
        '--font-body-transform' => !empty($settings['body_font_uppercase']) ? 'uppercase' : 'none',
        '--font-body-letter-spacing' => $settings['body_font_letter_spacing'] ?? '0',
        '--font-heading-size' => $settings['heading_font_size'] ?? 'clamp(1.8rem, 3vw, 2.8rem)',
        '--font-heading-transform' => $textTransform(!empty($settings['heading_font_capitalize']), !empty($settings['heading_font_uppercase'])),
        '--font-heading-letter-spacing' => $settings['heading_font_letter_spacing'] ?? '-0.04em',
        '--header-background-color' => $settings['header_background_color'] ?? '#ffffff',
        '--header-font-color' => $settings['header_font_color'] ?? '#181614',
        '--header-height' => $settings['header_height'] ?? '50px',
        '--header-logo-padding' => $settings['header_logo_padding'] ?? '0',
        '--header-logo-margin' => $settings['header_logo_margin'] ?? '0',
        '--font-menu-size' => $settings['menu_font_size'] ?? 'clamp(2rem, 4vw, 3.6rem)',
        '--menu-line-color' => $settings['menu_line_color'] ?? '#cbbfb0',
        '--menu-background-color' => $settings['menu_background_color'] ?? '#ffffff',
        '--menu-submenu-background-color' => $settings['menu_submenu_background_color'] ?? '#ffffff',
        '--menu-active-background-color' => $settings['menu_active_background_color'] ?? '#ffffff',
        '--menu-content-background-color' => $settings['menu_content_background_color'] ?? '#ffffff',
        '--font-menu-transform' => $textTransform(!empty($settings['menu_font_capitalize']), !empty($settings['menu_font_uppercase'])),
        '--font-menu-letter-spacing' => $settings['menu_font_letter_spacing'] ?? '-0.04em',
        '--font-submenu-size' => $settings['submenu_font_size'] ?? 'clamp(1.35rem, 2.7vw, 2.3rem)',
        '--font-submenu-transform' => $textTransform(!empty($settings['submenu_font_capitalize']), !empty($settings['submenu_font_uppercase'])),
        '--font-submenu-letter-spacing' => $settings['submenu_font_letter_spacing'] ?? '-0.04em',
    ];
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/css/site.css?v=<?= e((string) $cssVersion) ?>">
    <?php if ($fontFaces !== []): ?>
        <style id="theme-font-faces">
            <?php foreach ($fontFaces as $face): ?>
            @font-face {
                font-family: "<?= e($face['family']) ?>";
                src: url("<?= e($face['url']) ?>")<?= $face['format'] ? ' format("' . e($face['format']) . '")' : '' ?>;
                font-display: swap;
            }
            <?php endforeach; ?>
        </style>
    <?php endif; ?>
    <?php if ($fontVariables !== []): ?>
        <style id="theme-variables">
            :root {
                <?php foreach ($fontVariables as $variable => $value): ?>
                <?= $variable ?>: <?= $value ?>;
                <?php endforeach; ?>
            }
        </style>
    <?php endif; ?>
    <?php if ($trackingConfig['mode'] === 'gtm'): ?>
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','<?= e($trackingConfig['gtm_container_id']) ?>');
        </script>
    <?php elseif ($trackingConfig['mode'] === 'ga4'): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($trackingConfig['ga4_measurement_id']) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= e($trackingConfig['ga4_measurement_id']) ?>');
        </script>
    <?php endif; ?>
    <script defer src="/js/site.js?v=<?= e((string) $jsVersion) ?>"></script>
</head>
<body class="public-shell">
<?php if ($trackingConfig['mode'] === 'gtm'): ?>
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($trackingConfig['gtm_container_id']) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
<?php endif; ?>
<?= $app->view()->partial('partials/flash', ['flashMessages' => $flashMessages]) ?>
<?= $app->view()->partial('partials/public_header', ['locale' => $locale, 'settings' => $settings, 'navGroups' => $navGroups]) ?>
<?= $content ?>
<?= $app->view()->partial('partials/public_footer', ['settings' => $settings]) ?>
</body>
</html>

<?php
$currentRequestUri = $_SERVER['REQUEST_URI'] ?? ('/' . ($locale ?? 'pl'));
$currentPath = parse_url($currentRequestUri, PHP_URL_PATH) ?: '/' . ($locale ?? 'pl');
$currentQuery = parse_url($currentRequestUri, PHP_URL_QUERY);
$pathWithoutLocale = preg_replace('#^/(pl|en)(?=/|$)#', '', $currentPath) ?: '';
$pathWithoutLocale = $pathWithoutLocale !== '' ? $pathWithoutLocale : '';
$plSwitchPath = is_array($localeSwitch ?? null) ? (string) ($localeSwitch['pl'] ?? '/pl' . $pathWithoutLocale) : '/pl' . $pathWithoutLocale;
$enSwitchPath = is_array($localeSwitch ?? null) ? (string) ($localeSwitch['en'] ?? '/en' . $pathWithoutLocale) : '/en' . $pathWithoutLocale;
if (!is_array($localeSwitch ?? null) && is_string($currentQuery) && $currentQuery !== '') {
    $plSwitchPath .= '?' . $currentQuery;
    $enSwitchPath .= '?' . $currentQuery;
}
?>
<header class="site-header">
    <a class="skip-link" href="#main-content">Przejdź do treści</a>
    <div class="header-bar">
        <a class="brand" href="/<?= e($locale) ?>" data-accordion-nav>
            <?php if (!empty($settings['header_logo_media']['public_url'])): ?>
                <img
                    class="brand-logo"
                    src="<?= e($settings['header_logo_media']['public_url']) ?>"
                    alt="<?= e($settings['header_logo_media']['alt_text'] ?? ($settings['translation']['museum_name'] ?? 'Muzeum Książki Artystycznej')) ?>"
                >
            <?php else: ?>
                <span class="brand-title">MUZEUM KSIĄŻKI ARTYSTYCZNEJ</span>
            <?php endif; ?>
        </a>
        <div class="header-actions">
            <div class="site-locale-switch" aria-label="Wersja językowa">
                <a href="<?= e($plSwitchPath) ?>" class="<?= ($locale ?? 'pl') === 'pl' ? 'is-active' : '' ?>">PL</a>
                <a href="<?= e($enSwitchPath) ?>" class="<?= ($locale ?? 'pl') === 'en' ? 'is-active' : '' ?>">EN</a>
            </div>
            <?php if (!empty($settings['header_links_html'])): ?>
                <div class="header-custom-links"><?= $settings['header_links_html'] ?></div>
            <?php endif; ?>
            <?php if (!array_key_exists('header_show_cms_link', $settings) || !empty($settings['header_show_cms_link'])): ?>
                <a class="header-link header-link-muted" href="/admin/login">CMS</a>
            <?php endif; ?>
        </div>
    </div>
</header>

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
            <?php if (!empty($settings['header_links_html'])): ?>
                <div class="header-custom-links"><?= $settings['header_links_html'] ?></div>
            <?php endif; ?>
            <?php if (!array_key_exists('header_show_cms_link', $settings) || !empty($settings['header_show_cms_link'])): ?>
                <a class="header-link header-link-muted" href="/admin/login">CMS</a>
            <?php endif; ?>
        </div>
    </div>
</header>

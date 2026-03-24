<?php
$footerEnabled = !array_key_exists('footer_enabled', $settings) || !empty($settings['footer_enabled']);
$footerBarEnabled = !array_key_exists('footer_bar_enabled', $settings) || !empty($settings['footer_bar_enabled']);
$footerBarText = trim((string) ($settings['footer_bar_text'] ?? 'Copyright © 2026 peterwolf.pl dla Muzeum Książki Artystycznej w Łodzi All Rights Reserved'));
$footerBarPrimary = $footerBarText;
$footerBarSecondary = '';

if (preg_match('/^(.*?)(?:<br\s*\/?>|\s+)?(All Rights Reserved)\s*$/iu', $footerBarText, $matches)) {
    $footerBarPrimary = trim((string) ($matches[1] ?? ''));
    $footerBarSecondary = trim((string) ($matches[2] ?? ''));
}
?>

<?php if ($footerEnabled): ?>
    <footer class="site-footer">
        <div class="footer-grid">
            <div>
                <p class="footer-kicker">Muzeum</p>
                <h2><?= e($settings['translation']['museum_name'] ?? 'Muzeum Książki Artystycznej') ?></h2>
                <p><?= e($settings['translation']['organization_description'] ?? '') ?></p>
            </div>
            <div>
                <p class="footer-kicker">Wizyta</p>
                <p><?= nl2br(e($settings['translation']['opening_hours'] ?? '')) ?></p>
                <?php if (!empty($settings['translation']['visit_note'])): ?>
                    <p><?= e($settings['translation']['visit_note']) ?></p>
                <?php endif; ?>
                <?php if (!empty($settings['map_url'])): ?>
                    <p><a href="<?= e($settings['map_url']) ?>" target="_blank" rel="noreferrer">Otwórz mapę</a></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="footer-kicker">Kontakt</p>
                <p><a href="mailto:<?= e($settings['contact_email'] ?? '') ?>"><?= e($settings['contact_email'] ?? '') ?></a></p>
                <p><a href="tel:<?= e($settings['phone'] ?? '') ?>"><?= e($settings['phone'] ?? '') ?></a></p>
                <p><?= e(trim(($settings['street_address'] ?? '') . ', ' . ($settings['postal_code'] ?? '') . ' ' . ($settings['city'] ?? ''))) ?></p>
            </div>
        </div>
    </footer>
<?php endif; ?>

<?php if ($footerBarEnabled && $footerBarText !== ''): ?>
    <section class="site-footer-bar" aria-label="Copyright">
        <div class="site-footer-bar-inner">
            <div class="site-footer-bar-primary"><?= $footerBarPrimary !== '' ? $footerBarPrimary : $footerBarText ?></div>
            <?php if ($footerBarSecondary !== ''): ?>
                <div class="site-footer-bar-secondary"><?= e($footerBarSecondary) ?></div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class AdminChromeController extends Controller
{
    public function edit(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $settings = $this->app->settingRepository()->getForAdmin() ?? $this->blankForm();

        return $this->form($settings, []);
    }

    public function save(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $settings = $this->app->settingRepository()->getForAdmin() ?? $this->blankForm();
        $form = array_replace($settings, $this->hydrate($_POST));
        $errors = $this->validate($form);

        if ($errors !== []) {
            return $this->form($form, $errors);
        }

        $this->app->settingRepository()->save($form);
        $this->app->flash('success', 'Header i footer zostały zapisane.');

        return $this->redirect('/admin/chrome');
    }

    private function form(array $form, array $errors): string
    {
        return $this->render('admin/chrome', [
            'pageTitle' => 'Header i footer',
            'form' => $form,
            'errors' => $errors,
            'mediaAssets' => $this->app->mediaRepository()->allLocalized($this->adminLocale()),
        ], 'admin');
    }

    private function hydrate(array $input): array
    {
        return [
            'header_logo_media_id' => !empty($input['header_logo_media_id']) ? (int) $input['header_logo_media_id'] : null,
            'header_links_html' => $this->app->contentService()->sanitizeLinkHtml((string) ($input['header_links_html'] ?? '')),
            'header_show_cms_link' => !empty($input['header_show_cms_link']) ? 1 : 0,
            'header_background_color' => trim((string) ($input['header_background_color'] ?? '#ffffff')),
            'header_font_color' => trim((string) ($input['header_font_color'] ?? '#181614')),
            'header_height' => $this->sanitizeCssValue((string) ($input['header_height'] ?? '50px'), '50px'),
            'header_logo_padding' => $this->sanitizeCssValue((string) ($input['header_logo_padding'] ?? '0'), '0'),
            'header_logo_margin' => $this->sanitizeCssValue((string) ($input['header_logo_margin'] ?? '0'), '0'),
            'footer_enabled' => !empty($input['footer_enabled']) ? 1 : 0,
            'footer_bar_enabled' => !empty($input['footer_bar_enabled']) ? 1 : 0,
            'footer_bar_text' => $this->app->contentService()->sanitizeFooterBarHtml((string) ($input['footer_bar_text'] ?? '')),
        ];
    }

    private function validate(array $form): array
    {
        $errors = [];

        if ($form['header_background_color'] !== '' && !preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $form['header_background_color'])) {
            $errors['header_background_color'] = 'Podaj poprawny kolor w formacie HEX.';
        }

        if ($form['header_font_color'] !== '' && !preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $form['header_font_color'])) {
            $errors['header_font_color'] = 'Podaj poprawny kolor w formacie HEX.';
        }

        foreach (['header_height', 'header_logo_padding', 'header_logo_margin'] as $field) {
            if (!$this->isSafeCssValue((string) $form[$field])) {
                $errors[$field] = 'Podaj poprawną wartość CSS.';
            }
        }

        if ($form['footer_bar_enabled'] && trim((string) $form['footer_bar_text']) === '') {
            $errors['footer_bar_text'] = 'Treść dolnego paska stopki jest wymagana.';
        }

        return $errors;
    }

    private function blankForm(): array
    {
        return [
            'header_logo_media_id' => null,
            'header_links_html' => '',
            'header_show_cms_link' => 1,
            'header_background_color' => '#ffffff',
            'header_font_color' => '#181614',
            'header_height' => '50px',
            'header_logo_padding' => '0',
            'header_logo_margin' => '0',
            'footer_enabled' => 1,
            'footer_bar_enabled' => 1,
            'footer_bar_text' => 'Copyright © 2026 peterwolf.pl dla Muzeum Książki Artystycznej w Łodzi All Rights Reserved',
        ];
    }

    private function sanitizeCssValue(string $value, string $default): string
    {
        $value = trim($value);

        return $this->isSafeCssValue($value) ? $value : $default;
    }

    private function isSafeCssValue(string $value): bool
    {
        if ($value === '' || strlen($value) > 64) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9.,%()\-+\s]+$/', $value);
    }
}

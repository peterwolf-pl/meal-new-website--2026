<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;

final class AdminTypographyController extends Controller
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
            $this->app->flash('error', 'Nieprawidłowy token sesji. Odśwież formularz i spróbuj ponownie.');

            return $this->redirect('/admin/typography');
        }

        $settings = $this->app->settingRepository()->getForAdmin() ?? $this->blankForm();
        $form = array_replace($settings, $this->hydrateTypography($_POST));
        $errors = $this->validateTypography($form);

        if ($errors !== []) {
            return $this->form($form, $errors);
        }

        try {
            $this->app->settingRepository()->save($form);
            if ($this->app->settingRepository()->supportsThemeFonts()) {
                $this->app->flash('success', 'Typografia została zapisana.');
            } else {
                $this->app->flash('error', 'Typografia została zapisana, ale część zmian nie zapisze się bez aktualizacji bazy.');
            }
        } catch (Throwable $exception) {
            $this->app->flash('error', 'Nie udało się zapisać typografii: ' . $exception->getMessage());

            return $this->redirect('/admin/typography');
        }

        return $this->redirect('/admin/typography');
    }

    private function form(array $form, array $errors): string
    {
        $mediaAssets = $this->app->mediaRepository()->allLocalized($this->adminLocale());
        $fontPresets = $this->app->contentService()->fontPresets();

        return $this->render('admin/typography', [
            'pageTitle' => 'Typografia',
            'form' => $form,
            'errors' => $errors,
            'fontAssets' => array_values(array_filter(
                $mediaAssets,
                static fn(array $asset): bool => ($asset['kind'] ?? '') === 'font'
            )),
            'fontPresets' => $fontPresets,
            'supportsThemeFonts' => $this->app->settingRepository()->supportsThemeFonts(),
        ], 'admin');
    }

    private function hydrateTypography(array $input): array
    {
        return [
            'body_font_preset' => trim((string) ($input['body_font_preset'] ?? 'editorial-sans')) ?: 'editorial-sans',
            'body_font_media_id' => !empty($input['body_font_media_id']) ? (int) $input['body_font_media_id'] : null,
            'body_font_size' => $this->sanitizeCssValue((string) ($input['body_font_size'] ?? '1rem'), '1rem'),
            'body_font_uppercase' => !empty($input['body_font_uppercase']) ? 1 : 0,
            'body_font_letter_spacing' => $this->sanitizeCssValue((string) ($input['body_font_letter_spacing'] ?? '0'), '0'),
            'heading_font_preset' => trim((string) ($input['heading_font_preset'] ?? 'editorial-sans')) ?: 'editorial-sans',
            'heading_font_media_id' => !empty($input['heading_font_media_id']) ? (int) $input['heading_font_media_id'] : null,
            'heading_font_size' => $this->sanitizeCssValue((string) ($input['heading_font_size'] ?? 'clamp(1.8rem, 3vw, 2.8rem)'), 'clamp(1.8rem, 3vw, 2.8rem)'),
            'heading_font_capitalize' => !empty($input['heading_font_capitalize']) ? 1 : 0,
            'heading_font_uppercase' => !empty($input['heading_font_uppercase']) ? 1 : 0,
            'heading_font_letter_spacing' => $this->sanitizeCssValue((string) ($input['heading_font_letter_spacing'] ?? '-0.04em'), '-0.04em'),
            'menu_font_preset' => trim((string) ($input['menu_font_preset'] ?? 'editorial-sans')) ?: 'editorial-sans',
            'menu_font_media_id' => !empty($input['menu_font_media_id']) ? (int) $input['menu_font_media_id'] : null,
            'menu_font_size' => $this->sanitizeCssValue((string) ($input['menu_font_size'] ?? 'clamp(2rem, 4vw, 3.6rem)'), 'clamp(2rem, 4vw, 3.6rem)'),
            'menu_background_color' => $this->sanitizeColor((string) ($input['menu_background_color'] ?? '#ffffff'), '#ffffff'),
            'menu_submenu_background_color' => $this->sanitizeColor((string) ($input['menu_submenu_background_color'] ?? '#ffffff'), '#ffffff'),
            'menu_active_background_color' => $this->sanitizeColor((string) ($input['menu_active_background_color'] ?? '#ffffff'), '#ffffff'),
            'menu_content_background_color' => $this->sanitizeColor((string) ($input['menu_content_background_color'] ?? '#ffffff'), '#ffffff'),
            'menu_font_capitalize' => !empty($input['menu_font_capitalize']) ? 1 : 0,
            'menu_font_uppercase' => !empty($input['menu_font_uppercase']) ? 1 : 0,
            'menu_font_letter_spacing' => $this->sanitizeCssValue((string) ($input['menu_font_letter_spacing'] ?? '-0.04em'), '-0.04em'),
            'submenu_font_size' => $this->sanitizeCssValue((string) ($input['submenu_font_size'] ?? 'clamp(1.35rem, 2.7vw, 2.3rem)'), 'clamp(1.35rem, 2.7vw, 2.3rem)'),
            'submenu_font_capitalize' => !empty($input['submenu_font_capitalize']) ? 1 : 0,
            'submenu_font_uppercase' => !empty($input['submenu_font_uppercase']) ? 1 : 0,
            'submenu_font_letter_spacing' => $this->sanitizeCssValue((string) ($input['submenu_font_letter_spacing'] ?? '-0.04em'), '-0.04em'),
        ];
    }

    private function validateTypography(array $form): array
    {
        $errors = [];

        foreach (['body_font_preset', 'heading_font_preset', 'menu_font_preset'] as $field) {
            if (!$this->app->contentService()->fontPresetExists($form[$field])) {
                $errors[$field] = 'Wybierz poprawny preset fontu.';
            }
        }

        foreach ([
            'body_font_media_id' => 'font treści',
            'heading_font_media_id' => 'font nagłówków',
            'menu_font_media_id' => 'font menu',
        ] as $field => $label) {
            if (!$this->isValidFontMediaId($form[$field])) {
                $errors[$field] = 'Wybrany ' . $label . ' musi być medium typu font.';
            }
        }

        foreach ([
            'body_font_size',
            'body_font_letter_spacing',
            'heading_font_size',
            'heading_font_letter_spacing',
            'menu_font_size',
            'menu_font_letter_spacing',
            'submenu_font_size',
            'submenu_font_letter_spacing',
        ] as $field) {
            if (!$this->isSafeCssValue((string) $form[$field])) {
                $errors[$field] = 'Podaj poprawną wartość CSS.';
            }
        }

        if (!$this->isSafeColor((string) $form['menu_background_color'])) {
            $errors['menu_background_color'] = 'Podaj poprawny kolor w formacie HEX.';
        }

        if (!$this->isSafeColor((string) $form['menu_submenu_background_color'])) {
            $errors['menu_submenu_background_color'] = 'Podaj poprawny kolor w formacie HEX.';
        }

        if (!$this->isSafeColor((string) $form['menu_active_background_color'])) {
            $errors['menu_active_background_color'] = 'Podaj poprawny kolor w formacie HEX.';
        }

        if (!$this->isSafeColor((string) $form['menu_content_background_color'])) {
            $errors['menu_content_background_color'] = 'Podaj poprawny kolor w formacie HEX.';
        }

        return $errors;
    }

    private function blankForm(): array
    {
        return [
            'body_font_preset' => 'editorial-sans',
            'body_font_media_id' => null,
            'body_font_size' => '1rem',
            'body_font_uppercase' => 0,
            'body_font_letter_spacing' => '0',
            'heading_font_preset' => 'editorial-sans',
            'heading_font_media_id' => null,
            'heading_font_size' => 'clamp(1.8rem, 3vw, 2.8rem)',
            'heading_font_capitalize' => 0,
            'heading_font_uppercase' => 0,
            'heading_font_letter_spacing' => '-0.04em',
            'menu_font_preset' => 'editorial-sans',
            'menu_font_media_id' => null,
            'menu_font_size' => 'clamp(2rem, 4vw, 3.6rem)',
            'menu_background_color' => '#ffffff',
            'menu_submenu_background_color' => '#ffffff',
            'menu_active_background_color' => '#ffffff',
            'menu_content_background_color' => '#ffffff',
            'menu_font_capitalize' => 0,
            'menu_font_uppercase' => 1,
            'menu_font_letter_spacing' => '-0.04em',
            'submenu_font_size' => 'clamp(1.35rem, 2.7vw, 2.3rem)',
            'submenu_font_capitalize' => 0,
            'submenu_font_uppercase' => 0,
            'submenu_font_letter_spacing' => '-0.04em',
        ];
    }

    private function isValidFontMediaId(?int $mediaId): bool
    {
        if (!$mediaId) {
            return true;
        }

        $asset = $this->app->mediaRepository()->findLocalizedById($mediaId, 'pl');

        return $asset !== null && ($asset['kind'] ?? '') === 'font';
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

    private function sanitizeColor(string $value, string $default): string
    {
        $value = trim($value);

        return $this->isSafeColor($value) ? $value : $default;
    }

    private function isSafeColor(string $value): bool
    {
        return (bool) preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value);
    }
}

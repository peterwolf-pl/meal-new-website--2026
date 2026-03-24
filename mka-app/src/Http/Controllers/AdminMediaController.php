<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use RuntimeException;

final class AdminMediaController extends Controller
{
    public function index(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return $this->render('admin/media/index', [
            'pageTitle' => 'Media',
            'items' => $this->app->mediaRepository()->allLocalized($this->adminLocale()),
        ], 'admin');
    }

    public function create(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return $this->form($this->blankForm(), [], true);
    }

    public function edit(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $item = $this->app->mediaRepository()->findForAdmin($id);
        if (!$item) {
            http_response_code(404);
            return 'Nie znaleziono medium.';
        }

        return $this->form($item, [], false);
    }

    public function save(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $existing = !empty($_POST['id']) ? $this->app->mediaRepository()->findForAdmin((int) $_POST['id']) : null;
        try {
            $form = $this->hydrateForm($_POST, $_FILES['upload'] ?? null, $existing);
        } catch (RuntimeException $exception) {
            $fallback = $existing ?? $this->blankForm();
            $fallback = array_replace_recursive($fallback, [
                'id' => !empty($_POST['id']) ? (int) $_POST['id'] : null,
                'kind' => strtolower((string) ($_POST['kind'] ?? ($fallback['kind'] ?? 'image'))),
                'external_url' => trim((string) ($_POST['external_url'] ?? ($fallback['external_url'] ?? ''))),
                'is_decorative' => !empty($_POST['is_decorative']) ? 1 : 0,
                'translations' => [
                    'pl' => [
                        'title' => trim((string) ($_POST['translations']['pl']['title'] ?? '')),
                        'alt_text' => trim((string) ($_POST['translations']['pl']['alt_text'] ?? '')),
                        'caption' => trim((string) ($_POST['translations']['pl']['caption'] ?? '')),
                    ],
                    'en' => [
                        'title' => trim((string) ($_POST['translations']['en']['title'] ?? '')),
                        'alt_text' => trim((string) ($_POST['translations']['en']['alt_text'] ?? '')),
                        'caption' => trim((string) ($_POST['translations']['en']['caption'] ?? '')),
                    ],
                ],
            ]);

            return $this->form($fallback, ['upload' => $exception->getMessage()], empty($fallback['id']));
        }
        $errors = $this->validateForm($form, $_FILES['upload'] ?? null, $existing);

        if ($errors !== []) {
            return $this->form($form, $errors, empty($form['id']));
        }

        $id = $this->app->mediaRepository()->save($form);
        $this->app->flash('success', 'Medium zostało zapisane.');

        return $this->redirect('/admin/media/' . $id);
    }

    public function delete(int $id): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        $asset = $this->app->mediaRepository()->delete($id);
        if ($asset && !empty($asset['disk_path'])) {
            $this->deleteUploadedFile((string) $asset['disk_path']);
        }

        $this->app->flash('success', 'Medium zostało usunięte.');

        return $this->redirect('/admin/media');
    }

    private function form(array $form, array $errors, bool $isNew): string
    {
        return $this->render('admin/media/form', [
            'pageTitle' => $isNew ? 'Nowe medium' : 'Edycja medium',
            'form' => $form,
            'errors' => $errors,
            'isNew' => $isNew,
        ], 'admin');
    }

    private function hydrateForm(array $input, ?array $upload, ?array $existing): array
    {
        $kind = strtolower((string) ($input['kind'] ?? 'image'));
        $translations = $input['translations'] ?? [];
        $diskPath = $existing['disk_path'] ?? null;
        $originalName = $existing['original_name'] ?? null;
        $mimeType = $existing['mime_type'] ?? null;

        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            [$diskPath, $originalName, $mimeType] = $this->storeUpload($upload, $kind, $existing);
        }

        return [
            'id' => !empty($input['id']) ? (int) $input['id'] : null,
            'kind' => $kind,
            'disk_path' => $diskPath,
            'original_name' => $originalName,
            'external_url' => trim((string) ($input['external_url'] ?? ($existing['external_url'] ?? ''))),
            'mime_type' => $mimeType,
            'is_decorative' => !empty($input['is_decorative']) ? 1 : 0,
            'public_url' => $existing['public_url'] ?? null,
            'translations' => [
                'pl' => [
                    'title' => trim((string) ($translations['pl']['title'] ?? '')),
                    'alt_text' => trim((string) ($translations['pl']['alt_text'] ?? '')),
                    'caption' => trim((string) ($translations['pl']['caption'] ?? '')),
                ],
                'en' => [
                    'title' => trim((string) ($translations['en']['title'] ?? '')),
                    'alt_text' => trim((string) ($translations['en']['alt_text'] ?? '')),
                    'caption' => trim((string) ($translations['en']['caption'] ?? '')),
                ],
            ],
        ];
    }

    private function validateForm(array $form, ?array $upload, ?array $existing): array
    {
        $errors = [];
        $kind = $form['kind'];

        if (!in_array($kind, ['image', 'pdf', 'video', 'font'], true)) {
            $errors['kind'] = 'Nieprawidłowy typ medium.';
        }

        if ($form['translations']['pl']['title'] === '') {
            $errors['translations.pl.title'] = 'Tytuł PL jest wymagany.';
        }

        if ($kind === 'video') {
            if ($form['external_url'] === '') {
                $errors['external_url'] = 'Dla wideo podaj zewnętrzny adres URL.';
            }
        } elseif (empty($form['disk_path']) && $form['external_url'] === '') {
            $errors['upload'] = 'Dodaj plik lokalny albo adres zewnętrzny dla medium.';
        }

        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors['upload'] = 'Nie udało się wgrać pliku.';
        }

        return $errors;
    }

    private function storeUpload(array $upload, string $kind, ?array $existing): array
    {
        $tmpName = (string) ($upload['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException('Invalid upload.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpName);
        $allowed = [
            'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'pdf' => ['application/pdf'],
        ];

        if ($kind === 'font') {
            $extension = strtolower((string) pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION));
            $allowedExtensions = ['woff2', 'woff', 'ttf', 'otf'];
            $allowedMimeTypes = [
                'font/woff2',
                'font/woff',
                'application/font-woff',
                'application/x-font-woff',
                'font/ttf',
                'application/x-font-ttf',
                'application/x-font-truetype',
                'font/otf',
                'application/x-font-opentype',
                'application/vnd.ms-opentype',
                'application/font-sfnt',
                'font/sfnt',
                'application/octet-stream',
            ];

            if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
                throw new RuntimeException('Nieobsługiwany typ pliku fontu.');
            }
        } elseif ($kind !== 'video' && !in_array($mimeType, $allowed[$kind] ?? [], true)) {
            throw new RuntimeException('Nieobsługiwany typ pliku.');
        }

        $originalName = (string) ($upload['name'] ?? 'plik');
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = $this->app->contentService()->normalizeSlug(pathinfo($originalName, PATHINFO_FILENAME));
        $datePath = date('Y/m');
        $relativePath = $datePath . '/' . ($baseName ?: 'plik') . '-' . bin2hex(random_bytes(4)) . ($extension ? '.' . strtolower($extension) : '');
        $uploadRoot = $this->uploadRoot();
        $absolutePath = $uploadRoot . '/' . $relativePath;

        if (!is_dir(dirname($absolutePath))) {
            if (!mkdir(dirname($absolutePath), 0775, true) && !is_dir(dirname($absolutePath))) {
                throw new RuntimeException('Nie udało się utworzyć katalogu uploadów.');
            }
        }

        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new RuntimeException('Nie udało się zapisać pliku w katalogu uploadów. Sprawdź prawa zapisu dla upload/.');
        }

        if ($existing && !empty($existing['disk_path'])) {
            $this->deleteUploadedFile((string) $existing['disk_path']);
        }

        return [$relativePath, $originalName, $mimeType];
    }

    private function uploadRoot(): string
    {
        $roots = [
            $this->app->path('uploads'),
            dirname($this->app->path('root')) . '/upload',
            $this->app->path('root') . '/storage/uploads',
            dirname($this->app->path('root')) . '/public_html/storage/uploads',
        ];

        foreach ($roots as $root) {
            if (is_dir($root) && is_writable($root)) {
                return $root;
            }

            if (!is_dir($root) && @mkdir($root, 0775, true) && is_writable($root)) {
                return $root;
            }
        }

        throw new RuntimeException('Brak zapisywalnego katalogu uploadów. Utwórz i nadaj prawa zapisu dla upload/ obok public_html i mka-app.');
    }

    private function deleteUploadedFile(string $diskPath): void
    {
        $diskPath = ltrim($diskPath, '/');
        $roots = [
            $this->app->path('uploads'),
            dirname($this->app->path('root')) . '/upload',
            $this->app->path('root') . '/storage/uploads',
            dirname($this->app->path('root')) . '/public_html/storage/uploads',
        ];

        foreach ($roots as $root) {
            $absolute = $root . '/' . $diskPath;
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    private function blankForm(): array
    {
        return [
            'id' => null,
            'kind' => 'image',
            'disk_path' => null,
            'original_name' => null,
            'external_url' => '',
            'mime_type' => '',
            'is_decorative' => 0,
            'public_url' => null,
            'translations' => [
                'pl' => ['title' => '', 'alt_text' => '', 'caption' => ''],
                'en' => ['title' => '', 'alt_text' => '', 'caption' => ''],
            ],
        ];
    }
}

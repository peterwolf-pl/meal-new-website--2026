<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public function __construct(private readonly App $app)
    {
    }

    public function render(string $view, array $data = [], string $layout = 'public'): string
    {
        $shared = [
            'app' => $this->app,
            'flashMessages' => $this->app->pullFlashMessages(),
            'authUser' => $this->app->auth()->user(),
        ];

        $content = $this->renderFile($view, $shared + $data);

        return $this->renderFile('layouts/' . $layout, $shared + $data + ['content' => $content]);
    }

    public function partial(string $view, array $data = []): string
    {
        return $this->renderFile($view, ['app' => $this->app] + $data);
    }

    private function renderFile(string $view, array $data): string
    {
        $path = $this->app->path('views') . '/' . $view . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}

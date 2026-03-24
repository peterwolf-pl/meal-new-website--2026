<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class AdminDashboardController extends Controller
{
    public function index(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $locale = $this->adminLocale();
        $settings = $this->app->settingRepository()->getLocalized($locale) ?? [];

        return $this->render('admin/dashboard', [
            'pageTitle' => 'CMS',
            'contentCount' => $this->app->contentRepository()->count(),
            'mediaCount' => $this->app->mediaRepository()->count(),
            'statusCounts' => $this->app->contentRepository()->countByStatus(),
            'latestContent' => $this->app->contentRepository()->latestForDashboard($locale, 8),
            'latestMedia' => array_slice($this->app->mediaRepository()->allLocalized($locale), 0, 6),
            'googleInsights' => $this->app->googleIntegration()->dashboard($settings),
        ], 'admin');
    }

    public function setLocale(string $locale): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $this->setAdminLocale($locale);

        return $this->redirect($this->safeRedirectPath($_GET['return'] ?? null));
    }
}

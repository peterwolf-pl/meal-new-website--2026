<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class MetaController extends Controller
{
    public function robots(): string
    {
        header('Content-Type: text/plain; charset=UTF-8');

        return "User-agent: *\nDisallow: /admin\n\nSitemap: " . $this->app->baseUrl('/sitemap.xml') . "\n";
    }

    public function sitemap(): string
    {
        $settings = $this->app->settingRepository()->getLocalized('pl');
        $items = $this->app->contentRepository()->publishedForSitemap('pl');
        $urls = [
            $this->app->baseUrl('/pl'),
        ];

        foreach ($items as $item) {
            $urls[] = $this->app->baseUrl($this->app->contentService()->relativeUrl($item, 'pl'));
        }

        $urls = array_values(array_unique($urls));

        header('Content-Type: application/xml; charset=UTF-8');

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc></url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }
}

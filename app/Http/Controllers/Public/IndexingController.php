<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildWebsiteSitemap;
use App\Http\Controllers\Controller;
use App\Settings\GeneralSettings;
use Illuminate\Http\Response;

final class IndexingController extends Controller
{
    public function sitemap(BuildWebsiteSitemap $sitemap): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($sitemap->handle() as $url) {
            $xml .= '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
        }

        return response($xml.'</urlset>', headers: ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function robots(GeneralSettings $general): Response
    {
        $body = "User-agent: *\n";
        if (! $general->public_pages_enabled) {
            $body .= "Disallow: /\n";
        } else {
            $body .= "Disallow: /website\nDisallow: /settings\nDisallow: /account\nDisallow: /dashboard\n";
        }
        $body .= 'Sitemap: '.mb_rtrim((string) config('app.url'), '/').route('sitemap', absolute: false)."\n";

        return response($body, headers: ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}

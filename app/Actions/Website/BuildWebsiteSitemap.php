<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\WebsiteContent;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;

final readonly class BuildWebsiteSitemap
{
    public function __construct(private GeneralSettings $general, private WebsiteSettings $settings, private ResolvePublicSeo $seo) {}

    /** @return list<string> */
    public function handle(): array
    {
        $this->general->refresh();
        $this->settings->refresh();
        if (! $this->general->public_pages_enabled) {
            return [];
        }
        // Read current snapshots so scheduled visibility and switch changes cannot serve stale entries.
        $records = WebsiteContent::query()->select(['page_key', 'published_slug', 'published->seo->robots as robots'])->whereNotNull('published')->where('published_at', '<=', now())->get();
        $urls = [];
        foreach (['home', 'contact', 'article-index'] as $kind) {
            $record = $records->firstWhere('page_key', $kind);
            if (($record?->getAttribute('robots') ?? $this->settings->seo['robots']) === 'index,follow') {
                $urls[] = $this->seo->canonical($kind);
            }
        }
        foreach ($records as $record) {
            if (($record->getAttribute('robots') ?? $this->settings->seo['robots']) !== 'index,follow') {
                continue;
            }
            if ($record->page_key === 'about') {
                $urls[] = $this->seo->canonical('about');
            } elseif ($record->page_key === null && $record->published_slug !== null) {
                $urls[] = $this->seo->canonical('article', $record->published_slug);
            }
        }

        return $urls;
    }
}

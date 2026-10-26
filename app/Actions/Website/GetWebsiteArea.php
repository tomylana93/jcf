<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Enums\WebsitePage;
use App\Enums\WebsitePreset;
use App\Models\WebsiteContent;
use App\Settings\WebsiteSettings;

final readonly class GetWebsiteArea
{
    public function __construct(private WebsiteSettings $settings) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $this->settings->refresh();
        $contents = WebsiteContent::query()->whereIn('page_key', [...array_map(static fn (WebsitePage $page): string => $page->value, WebsitePage::cases())])->get()->keyBy('page_key');

        return [
            'pages' => array_map(static function (WebsitePage $page) use ($contents): array {
                $content = $contents->get($page->value);

                return $content === null
                    ? ['page' => $page->value, 'label' => $page->label(), 'status' => null]
                    : ['page' => $page->value, 'label' => $page->label(), 'status' => [
                        'draft_version' => $content->draft_version,
                        'published' => $content->published !== null,
                        'published_at' => $content->published_at?->toIso8601String(),
                    ]];
            }, WebsitePage::cases()),
            'preset' => $this->settings->preset,
            'presetOptions' => [...array_map(static fn (WebsitePreset $preset): array => ['value' => $preset->value, 'label' => $preset->label()], WebsitePreset::cases())],
            'publicPagesEnabled' => $this->settings->public_pages_enabled,
        ];
    }
}

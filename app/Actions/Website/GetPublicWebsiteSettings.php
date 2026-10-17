<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Facades\Cache;

final readonly class GetPublicWebsiteSettings
{
    public function __construct(private GeneralSettings $general, private WebsiteSettings $settings) {}

    /** @return array<string, mixed>|null */
    public function handle(): ?array
    {
        if (! $this->general->public_pages_enabled) {
            return null;
        }
        $this->settings->refresh();

        return Cache::remember('website.settings.'.$this->settings->public_version, 3600, fn (): array => $this->settings->toCollection()->only(['preset', 'identity', 'seo', 'contact', 'navigation', 'footer', 'preferences'])->all());
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\WebsiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class InvalidateWebsiteCache
{
    public function handle(?WebsiteContent $previous = null, ?string $settingsVersion = null): void
    {
        $key = $previous?->publication_version === null ? null : 'website.content.'.$previous->id.'.'.$previous->publication_version;
        DB::afterCommit(static function () use ($key, $settingsVersion): void {
            if ($key !== null) {
                Cache::forget($key);
            }
            if ($settingsVersion !== null) {
                Cache::forget('website.settings.'.$settingsVersion);
            }
            Cache::forget('website.sitemap');
        });
    }
}

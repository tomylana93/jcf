<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\BrandSettings;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final readonly class CleanOrphanedBrandImages
{
    public function __construct(private BrandSettings $settings) {}

    /** @return list<string> */
    public function handle(bool $dryRun, int $graceMinutes): array
    {
        $disk = Storage::disk('public');
        $settings = $this->settings;
        $activePaths = array_filter([$settings->logo_path, $settings->logo_text_path, $settings->favicon_path, $settings->auth_background_path]);
        $cutoff = now()->subMinutes($graceMinutes)->getTimestamp();
        $result = [];

        foreach ($disk->files('brand') as $path) {
            if (! preg_match('/^brand\/[a-zA-Z0-9]{40}\.(?:jpg|jpeg|png|gif|webp)$/i', $path)
                || in_array($path, $activePaths, true)
                || ($graceMinutes > 0 && $disk->lastModified($path) > $cutoff)) {
                continue;
            }

            $result[] = $path;
            throw_if(! $dryRun && ! $disk->delete($path), RuntimeException::class, "Failed to delete orphaned brand file: {$path}");
        }

        return $result;
    }
}

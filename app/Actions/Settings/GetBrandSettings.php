<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\BrandSettings;
use Illuminate\Support\Facades\Storage;

final class GetBrandSettings
{
    public function __construct(public BrandSettings $settings) {}

    /** @return array{logo_type: string, logo_text_url: ?string, logo_url: ?string, favicon_url: ?string, auth_background_url: ?string, color: string} */
    public function handle(): array
    {
        return [
            'logo_type' => $this->settings->logo_type->value,
            'logo_text_url' => $this->settings->logo_text_path ? Storage::disk('public')->url($this->settings->logo_text_path) : null,
            'logo_url' => $this->settings->logo_path ? Storage::disk('public')->url($this->settings->logo_path) : null,
            'favicon_url' => $this->settings->favicon_path ? Storage::disk('public')->url($this->settings->favicon_path) : null,
            'auth_background_url' => $this->settings->auth_background_path ? Storage::disk('public')->url($this->settings->auth_background_path) : null,
            'color' => $this->settings->color->value,
        ];
    }
}

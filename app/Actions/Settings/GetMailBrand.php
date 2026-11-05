<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\LogoType;
use App\Settings\BrandSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Storage;

final class GetMailBrand
{
    public function __construct(
        public BrandSettings $brandSettings,
        public GeneralSettings $generalSettings,
        public MailSettings $mailSettings,
    ) {}

    /** @return array{name: string, logo_url: ?string, accent: string, footer_text: string, show_name: bool} */
    public function handle(): array
    {
        $this->brandSettings->refresh();
        $this->generalSettings->refresh();
        $this->mailSettings->refresh();

        $logoPath = $this->brandSettings->logo_type === LogoType::LogoText
            ? $this->brandSettings->logo_text_path
            : $this->brandSettings->logo_path;
        $fallback = $this->brandSettings->logo_type === LogoType::LogoText ? 'logo.png' : 'logo-wordmark.png';

        return [
            'show_name' => ! $this->mailSettings->show_logo || $this->brandSettings->logo_type === LogoType::LogoText,
            'name' => $this->generalSettings->site_name,
            'logo_url' => $this->mailSettings->show_logo
                ? ($logoPath
                    ? url(Storage::disk('public')->url($logoPath))
                    : asset($fallback))
                : null,
            'accent' => $this->brandSettings->color->mailAccent(),
            'footer_text' => $this->mailSettings->footer_text,
        ];
    }
}

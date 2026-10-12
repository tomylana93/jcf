<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\GeneralSettings;

final class GetGeneralSettings
{
    public function __construct(public GeneralSettings $settings) {}

    /** @return array{site_name: string, site_description: string, support_email: ?string, public_pages_enabled: bool} */
    public function handle(): array
    {
        return [
            'site_name' => $this->settings->site_name,
            'site_description' => $this->settings->site_description,
            'support_email' => $this->settings->support_email,
            'public_pages_enabled' => $this->settings->public_pages_enabled,
        ];
    }
}

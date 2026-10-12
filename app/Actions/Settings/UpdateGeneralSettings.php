<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\GeneralSettings;

final class UpdateGeneralSettings
{
    public function __construct(public GeneralSettings $settings) {}

    /** @param array{site_name: string, site_description: ?string, support_email: ?string, public_pages_enabled: bool} $data */
    public function handle(array $data): void
    {

        $this->settings->site_name = $data['site_name'];
        $this->settings->site_description = $data['site_description'] ?? '';
        $this->settings->support_email = $data['support_email'];
        $this->settings->public_pages_enabled = $data['public_pages_enabled'];
        $this->settings->save();
    }
}

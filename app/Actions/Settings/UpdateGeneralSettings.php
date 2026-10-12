<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audit\RecordSettingsChange;
use App\Models\User;
use App\Settings\GeneralSettings;

final class UpdateGeneralSettings
{
    public function __construct(public GeneralSettings $settings, public RecordSettingsChange $recordSettingsChange) {}

    /** @param array{site_name: string, site_description: ?string, support_email: ?string, public_pages_enabled: bool} $data */
    public function handle(array $data, ?User $actor = null): void
    {
        $this->recordSettingsChange->handle($this->settings, $actor, function () use ($data): void {

            $this->settings->site_name = $data['site_name'];
            $this->settings->site_description = $data['site_description'] ?? '';
            $this->settings->support_email = $data['support_email'];
            $this->settings->public_pages_enabled = $data['public_pages_enabled'];
            $this->settings->save();
        });
    }
}

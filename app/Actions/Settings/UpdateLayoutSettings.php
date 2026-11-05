<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audit\RecordSettingsChange;
use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use App\Models\User;
use App\Settings\LayoutSettings;

final readonly class UpdateLayoutSettings
{
    public function __construct(private LayoutSettings $settings, private RecordSettingsChange $recordSettingsChange) {}

    /** @param array{app_layout: string, auth_layout: string} $data */
    public function handle(?User $actor, array $data): void
    {
        $this->recordSettingsChange->handle($actor, $this->settings, function () use ($data): void {

            $this->settings->app_layout = AppLayout::from($data['app_layout']);
            $this->settings->auth_layout = AuthLayout::from($data['auth_layout']);
            $this->settings->save();
        });
    }
}

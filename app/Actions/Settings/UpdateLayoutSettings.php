<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audit\RecordSettingsChange;
use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use App\Models\User;
use App\Settings\LayoutSettings;

final class UpdateLayoutSettings
{
    public function __construct(public LayoutSettings $settings, public RecordSettingsChange $recordSettingsChange) {}

    /** @param array{app_layout: string, auth_layout: string} $data */
    public function handle(array $data, ?User $actor = null): void
    {
        $this->recordSettingsChange->handle($this->settings, $actor, function () use ($data): void {

            $this->settings->app_layout = AppLayout::from($data['app_layout']);
            $this->settings->auth_layout = AuthLayout::from($data['auth_layout']);
            $this->settings->save();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use App\Settings\LayoutSettings;

final class UpdateLayoutSettings
{
    public function __construct(public LayoutSettings $settings) {}

    /** @param array{app_layout: string, auth_layout: string} $data */
    public function handle(array $data): void
    {

        $this->settings->app_layout = AppLayout::from($data['app_layout']);
        $this->settings->auth_layout = AuthLayout::from($data['auth_layout']);
        $this->settings->save();
    }
}

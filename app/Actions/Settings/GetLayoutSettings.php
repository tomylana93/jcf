<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\LayoutSettings;

final class GetLayoutSettings
{
    public function __construct(public LayoutSettings $settings) {}

    /** @return array{app_layout: string, auth_layout: string} */
    public function handle(): array
    {
        return [
            'app_layout' => $this->settings->app_layout->value,
            'auth_layout' => $this->settings->auth_layout->value,
        ];
    }
}

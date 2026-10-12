<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use Spatie\LaravelSettings\Settings;

final class LayoutSettings extends Settings
{
    public AppLayout $app_layout;

    public AuthLayout $auth_layout;

    public static function group(): string
    {
        return 'layout';
    }
}

<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class GeneralSettings extends Settings
{
    public string $site_name;

    public string $site_description;

    public ?string $support_email = null;

    public bool $public_pages_enabled;

    public static function group(): string
    {
        return 'general';
    }
}

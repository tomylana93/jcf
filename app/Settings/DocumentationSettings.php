<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class DocumentationSettings extends Settings
{
    public bool $enabled;

    public static function group(): string
    {
        return 'documentation';
    }
}

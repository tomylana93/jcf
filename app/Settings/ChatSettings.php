<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class ChatSettings extends Settings
{
    public bool $enabled;

    public static function group(): string
    {
        return 'chat';
    }
}

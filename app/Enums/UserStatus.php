<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum UserStatus: string
{
    use HasLabel, HasOptions;

    case Active = 'active';
    case Disabled = 'disabled';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'default',
            self::Disabled => 'secondary',
        };
    }
}

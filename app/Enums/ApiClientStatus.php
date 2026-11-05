<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum ApiClientStatus: string
{
    use HasLabel, HasOptions;

    case Active = 'active';
    case Disabled = 'disabled';
}

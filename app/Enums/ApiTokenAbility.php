<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum ApiTokenAbility: string
{
    use HasLabel, HasOptions;

    case Read = 'read';
    case Write = 'write';
}

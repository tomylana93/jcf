<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum AuthLayout: string
{
    use HasLabel, HasOptions;

    case Split = 'split';
    case Card = 'card';
    case Simple = 'simple';
}

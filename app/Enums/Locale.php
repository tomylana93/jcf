<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum Locale: string
{
    use HasLabel, HasOptions;

    case English = 'en';
    case Indonesian = 'id';
}

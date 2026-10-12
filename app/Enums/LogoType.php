<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum LogoType: string
{
    use HasLabel, HasOptions;

    case LogoOnly = 'logo_only';
    case LogoText = 'logo_text';
}

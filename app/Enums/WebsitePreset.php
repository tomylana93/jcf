<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum WebsitePreset: string
{
    use HasLabel, HasOptions;

    case Corporate = 'corporate';
    case Editorial = 'editorial';
    case Minimal = 'minimal';
}

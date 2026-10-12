<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum AppLayout: string
{
    use HasLabel, HasOptions;

    case Sidebar = 'sidebar';
    case Header = 'header';
}

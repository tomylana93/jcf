<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum ApiTokenExpiration: string
{
    use HasLabel, HasOptions;

    case ThirtyDays = '30';
    case NinetyDays = '90';
    case OneYear = '365';

    public function days(): int
    {
        return (int) $this->value;
    }
}

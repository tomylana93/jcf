<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

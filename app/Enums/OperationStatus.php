<?php

declare(strict_types=1);

namespace App\Enums;

enum OperationStatus: string
{
    case Idle = 'idle';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}

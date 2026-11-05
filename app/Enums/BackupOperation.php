<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Long-running backup operations and the last status each reported to the backup page.
 */
enum BackupOperation: string
{
    case Backup = 'backup';
    case Restore = 'restore';

    private const int StatusTtlSeconds = 86400;

    public function status(): OperationStatus
    {
        return OperationStatus::tryFrom((string) $this->store()->get($this->statusKey())) ?? OperationStatus::Idle;
    }

    public function mark(OperationStatus $status): void
    {
        $this->store()->put($this->statusKey(), $status->value, self::StatusTtlSeconds);
    }

    public function statusKey(): string
    {
        return 'system.'.$this->value.'_status';
    }

    /**
     * Restore flushes the default cache store, so its status lives in the file store to survive the flush.
     */
    private function store(): Repository
    {
        return match ($this) {
            self::Backup => Cache::store(),
            self::Restore => Cache::store('file'),
        };
    }
}

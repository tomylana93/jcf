<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Filesystem\Filesystem;

final readonly class GetBackupLock
{
    public function handle(): Lock
    {
        return new FileStore(new Filesystem, config('jalara.operations.backup_lock_directory'))->lock('system.backup', 1900);
    }
}

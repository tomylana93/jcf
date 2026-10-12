<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Filesystem\Filesystem;

final class GetBackupLock
{
    public function handle(): Lock
    {
        return new FileStore(new Filesystem, config('operations.backup_lock_directory'))->lock('system.backup', 1900);
    }
}

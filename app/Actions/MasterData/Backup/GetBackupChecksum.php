<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class GetBackupChecksum
{
    public function handle(string $filename): string
    {
        throw_if(preg_match('/^[A-Za-z0-9-]+\.zip$/D', $filename) !== 1, RuntimeException::class, 'Invalid backup filename.');
        $path = config('backup.backup.name').'/'.$filename;
        throw_unless(Storage::disk('backups')->exists($path), RuntimeException::class, 'Backup archive missing.');
        $stream = Storage::disk('backups')->readStream($path);
        $hash = hash_init('sha256');
        try {
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}

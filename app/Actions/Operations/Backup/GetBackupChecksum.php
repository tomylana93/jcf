<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Support\Facades\Storage;

final readonly class GetBackupChecksum
{
    public function __construct(private GetBackupPath $getBackupPath) {}

    public function handle(string $filename): string
    {
        $path = $this->getBackupPath->handle($filename, mustExist: true);
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

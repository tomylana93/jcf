<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;
use ZipArchive;

final readonly class ExtractBackup
{
    public function __construct(private GetBackupPath $getBackupPath) {}

    public function handle(string $filename, string $checksum, bool $requireEncryption = false): TemporaryDirectory
    {
        $path = Storage::disk('backups')->path($this->getBackupPath->handle($filename));
        $actualChecksum = is_file($path) ? hash_file('sha256', $path) : false;
        throw_if($actualChecksum === false || ! hash_equals($actualChecksum, $checksum), RuntimeException::class, 'Backup checksum mismatch or archive missing.');
        $zip = new ZipArchive;
        throw_if($zip->open($path) !== true, RuntimeException::class, 'Invalid backup archive.');
        $zip->setPassword((string) config('backup.backup.password'));
        $directory = new TemporaryDirectory(config('jalara.operations.restore_directory'))->name((string) Str::uuid())->create();
        try {
            $total = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                throw_if($entry === false, RuntimeException::class, 'Invalid archive entry.');
                $name = $entry['name'];
                $total += $entry['size'];
                throw_if($total > config('jalara.operations.restore_max_bytes')
                    || str_contains($name, '..') || str_contains($name, '\\') || str_contains($name, ':')
                    || (! str_starts_with($name, 'storage/app/private/') && ! str_starts_with($name, 'storage/app/public/') && ! preg_match('#^db-dumps/[A-Za-z0-9_-]+\.sql$#D', $name)), RuntimeException::class, 'Unsafe or unsupported backup entry.');
                $opsys = 0;
                $attributes = 0;
                $zip->getExternalAttributesIndex($index, $opsys, $attributes);
                throw_if(($attributes >> 16 & 0170000) === 0120000, RuntimeException::class, 'Archive symlinks are not allowed.');
                if (str_ends_with($name, '/')) {
                    continue;
                }
                throw_if($requireEncryption && $entry['encryption_method'] !== ZipArchive::EM_AES_256, RuntimeException::class, 'Backup entries must use AES-256 encryption.');
                $stream = $zip->getStream($name);
                throw_if($stream === false, RuntimeException::class, 'Archive password or content is invalid.');
                $target = $directory->path($name);
                File::ensureDirectoryExists(dirname($target));
                $output = fopen($target, 'wb');
                throw_if($output === false, RuntimeException::class, 'Cannot write staged backup file.');
                try {
                    throw_if(stream_copy_to_stream($stream, $output) !== $entry['size'], RuntimeException::class, 'Archive entry is incomplete.');
                } finally {
                    fclose($stream);
                    fclose($output);
                }
            }

            return $directory;
        } catch (Throwable $exception) {
            $directory->delete();
            throw $exception;
        } finally {
            $zip->close();
        }
    }
}

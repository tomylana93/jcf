<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Resolve an archive filename to its path on the backups disk, rejecting names outside the archive pattern.
 */
final readonly class GetBackupPath
{
    public const string FILENAME_PATTERN = '[A-Za-z0-9-]+\.zip';

    public static function directory(): string
    {
        return (string) config('backup.backup.name');
    }

    public static function isValidFilename(string $filename): bool
    {
        return preg_match('/^'.self::FILENAME_PATTERN.'$/D', $filename) === 1;
    }

    /**
     * @throws RecordsNotFoundException
     */
    public function handle(string $filename, bool $mustExist = false): string
    {
        throw_unless(self::isValidFilename($filename), RecordsNotFoundException::class, 'Invalid backup filename.');
        $path = self::directory().'/'.$filename;
        throw_if($mustExist && ! Storage::disk('backups')->exists($path), RecordsNotFoundException::class, 'Backup archive missing.');

        return $path;
    }
}

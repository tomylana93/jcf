<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final readonly class UploadBackup
{
    public function __construct(private ExtractBackup $extractBackup, private GetBackupChecksum $getBackupChecksum, private GetBackupPath $getBackupPath) {}

    public function handle(UploadedFile $archive): string
    {
        $filename = 'upload-'.Str::uuid().'.zip';
        $path = $this->getBackupPath->handle($filename);
        $archive->storeAs(GetBackupPath::directory(), $filename, 'backups');
        try {
            $directory = $this->extractBackup->handle($filename, $this->getBackupChecksum->handle($filename), true);
            try {
                throw_unless(is_file($directory->path('db-dumps/postgresql-'.config('database.default').'.sql')), RuntimeException::class, 'The source database dump is missing.');
            } finally {
                $directory->delete();
            }
        } catch (Throwable $exception) {
            Storage::disk('backups')->delete($path);
            report($exception);
            throw ValidationException::withMessages(['archive' => __('backup.error.invalid_archive')]);
        }

        return $filename;
    }
}

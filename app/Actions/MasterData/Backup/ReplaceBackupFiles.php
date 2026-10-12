<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Closure;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;

final class ReplaceBackupFiles
{
    public function handle(TemporaryDirectory $directory, Closure $importDatabase): void
    {
        $root = config('operations.restore_files_directory');
        $swapped = [];
        try {
            foreach (['private', 'public'] as $name) {
                $target = $root.'/'.$name;
                $previous = $directory->path().'/previous/'.$name;
                $replacement = $directory->path().'/storage/app/'.$name;
                File::ensureDirectoryExists($replacement);
                File::ensureDirectoryExists(dirname($previous));
                throw_if(is_link($target), RuntimeException::class, 'Restore does not support symlinked application storage.');
                if (is_dir($target)) {
                    throw_unless(File::moveDirectory($target, $previous), RuntimeException::class, 'Cannot stage the current application files.');
                }
                $swapped[] = $name;
                throw_unless(File::moveDirectory($replacement, $target), RuntimeException::class, 'Cannot replace application files.');
            }
            $importDatabase();
        } catch (Throwable $exception) {
            foreach (array_reverse($swapped) as $name) {
                File::deleteDirectory($root.'/'.$name);
                $previous = $directory->path().'/previous/'.$name;
                if (is_dir($previous)) {
                    throw_unless(File::moveDirectory($previous, $root.'/'.$name), RuntimeException::class, 'File rollback failed; keep maintenance enabled and recover the staged files.');
                }
            }
            throw $exception;
        }
    }
}

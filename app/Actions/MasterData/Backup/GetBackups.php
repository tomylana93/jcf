<?php

declare(strict_types=1);

namespace App\Actions\MasterData\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class GetBackups
{
    /** @return list<array{filename: string, size: int, created_at: string}> */
    public function handle(): array
    {
        $disk = Storage::disk('backups');

        return array_values(collect($disk->files(config('backup.backup.name')))
            ->filter(fn (string $path): bool => preg_match('/^[A-Za-z0-9-]+\.zip$/D', basename($path)) === 1)
            ->map(fn (string $path): array => [
                'filename' => basename($path),
                'size' => $disk->size($path),
                'created_at' => CarbonImmutable::createFromTimestamp($disk->lastModified($path))->toIso8601String(),
            ])->sortByDesc('filename')->values()->all());
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final readonly class GetBackups
{
    /** @return list<array{filename: string, size: int, created_at: CarbonImmutable}> */
    public function handle(): array
    {
        $disk = Storage::disk('backups');

        return array_values(collect($disk->files(GetBackupPath::directory()))
            ->filter(fn (string $path): bool => GetBackupPath::isValidFilename(basename($path)))
            ->map(fn (string $path): array => [
                'filename' => basename($path),
                'size' => $disk->size($path),
                'created_at' => CarbonImmutable::createFromTimestamp($disk->lastModified($path)),
            ])->sortByDesc('filename')->values()->all());
    }
}

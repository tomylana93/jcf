<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use App\Enums\Module;
use App\Support\Contracts\HealthCheck;
use App\Support\FeatureManager;
use Illuminate\Support\Facades\Storage;

final readonly class CheckBackupHealth implements HealthCheck
{
    public function __construct(private FeatureManager $features) {}

    public function name(): string
    {
        return 'backup';
    }

    public function handle(): string
    {
        if (! $this->features->enabled(Module::Backup)) {
            return 'disabled';
        }
        if (blank(config('backup.backup.password'))) {
            return 'not_configured';
        }
        $disk = Storage::disk('backups');
        $latest = collect($disk->files(GetBackupPath::directory()))->filter(fn (string $path): bool => GetBackupPath::isValidFilename(basename($path)))->map(fn (string $path): int => $disk->lastModified($path))->max();

        return is_int($latest) && $latest >= now()->subDays(2)->timestamp ? 'recent' : 'stale';
    }
}

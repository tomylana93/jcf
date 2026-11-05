<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Module;
use Illuminate\Foundation\PackageManifest;
use Spatie\Backup\BackupServiceProvider;

final class FeaturePackageManifest extends PackageManifest
{
    /** @return list<string> */
    public function providers(): array
    {
        return array_values(array_filter(parent::providers(), static fn (string $provider): bool => $provider !== BackupServiceProvider::class || Module::Backup->flagged()));
    }
}

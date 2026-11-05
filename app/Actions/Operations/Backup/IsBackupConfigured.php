<?php

declare(strict_types=1);

namespace App\Actions\Operations\Backup;

use App\Enums\Module;
use App\Support\FeatureManager;

/**
 * Whether archives can be written and read: the module is enabled and an archive password is set.
 */
final readonly class IsBackupConfigured
{
    public function __construct(private FeatureManager $features) {}

    public function handle(): bool
    {
        return $this->features->enabled(Module::Backup) && filled(config('backup.backup.password'));
    }
}

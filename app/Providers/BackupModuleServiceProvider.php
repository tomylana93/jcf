<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Operations\Backup\CheckBackupHealth;
use App\Actions\Operations\Backup\IsBackupConfigured;
use App\Enums\Module;
use App\Support\Contracts\HealthCheck;
use App\Support\Contracts\MonitoredQueue;
use App\Support\FeatureManager;
use App\Support\Queues\BackupQueue;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Application-side registration for the backup module; the Spatie package provider is gated separately by FeaturePackageManifest.
 */
final class BackupModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(CheckBackupHealth::class, HealthCheck::class);
        $this->app->tag(BackupQueue::class, MonitoredQueue::class);
    }

    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            // Cleanup and monitoring only inspect existing archives, so they do not require the archive password.
            $schedule->command('system:backup')->dailyAt('01:30')->withoutOverlapping(40)->when(fn (): bool => resolve(IsBackupConfigured::class)->handle());
            $schedule->command('backup:clean')->dailyAt('01:00')->withoutOverlapping()->when(fn (): bool => resolve(FeatureManager::class)->enabled(Module::Backup));
            $schedule->command('backup:monitor')->dailyAt('10:00')->withoutOverlapping()->when(fn (): bool => resolve(FeatureManager::class)->enabled(Module::Backup));
        });
    }
}

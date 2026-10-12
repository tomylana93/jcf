<?php

declare(strict_types=1);

use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('avatars:prune')->daily()->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [AuditLog::class]])->daily()->withoutOverlapping();

Schedule::command('system:heartbeat')->everyMinute()->withoutOverlapping();

Schedule::command('system:backup')->dailyAt('01:30')->withoutOverlapping(40)->when(fn (): bool => (bool) config('operations.backup_enabled') && (bool) config('backup.backup.password'));
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping()->when(fn (): bool => (bool) config('operations.backup_enabled'));
Schedule::command('backup:monitor')->dailyAt('10:00')->withoutOverlapping()->when(fn (): bool => (bool) config('operations.backup_enabled'));

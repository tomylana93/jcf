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

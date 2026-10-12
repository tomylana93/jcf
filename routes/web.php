<?php

declare(strict_types=1);

use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Notification\NotificationController;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn (GeneralSettings $settings) => $settings->public_pages_enabled ? Inertia::render('public/Home') : to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'update'])->whereUuid('notification')->name('notifications.read');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

require __DIR__.'/account.php';

require __DIR__.'/master-data.php';

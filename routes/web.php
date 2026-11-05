<?php

declare(strict_types=1);

use App\Enums\Module;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Permission\UserPermissionController;
use App\Http\Controllers\Search\GlobalSearchController;
use App\Http\Controllers\StartController;
use App\Http\Controllers\System\SystemController;
use Illuminate\Support\Facades\Route;

Route::get('/', StartController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('search', GlobalSearchController::class)->middleware(['throttle:60,1', 'no-store'])->name('search');
    Route::get('permissions', [UserPermissionController::class, 'index'])->name('permissions.index');
    Route::get('permissions/{user}/edit', [UserPermissionController::class, 'edit'])->whereUuid('user')->name('permissions.edit');
    Route::patch('permissions/{user}', [UserPermissionController::class, 'update'])->whereUuid('user')->name('permissions.update');
    Route::get('system', [SystemController::class, 'index'])->name('system.index');
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'update'])->whereUuid('notification')->name('notifications.read');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

if (Module::Chat->available()) {
    Route::middleware('feature:chat')->group(__DIR__.'/chat.php');
}

require __DIR__.'/settings.php';

require __DIR__.'/account.php';

require __DIR__.'/master-data.php';

if (Module::Documentation->available()) {
    Route::middleware('feature:documentation')->group(__DIR__.'/documentation.php');
}

if (Module::Backup->available()) {
    Route::middleware('feature:backup')->group(__DIR__.'/backups.php');
}

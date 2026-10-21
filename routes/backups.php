<?php

declare(strict_types=1);

use App\Http\Controllers\MasterData\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('master-data')->name('master-data.')->group(function (): void {
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::middleware(['password.confirm', 'throttle:3,1'])->group(function (): void {
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::post('backups/upload', [BackupController::class, 'upload'])->name('backups.upload');
        Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->where('backup', '[A-Za-z0-9-]+\\.zip')->name('backups.restore');
        Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->where('backup', '[A-Za-z0-9-]+\\.zip')->name('backups.destroy');
    });
    Route::get('backups/{backup}/download', [BackupController::class, 'download'])->where('backup', '[A-Za-z0-9-]+\\.zip')->middleware('password.confirm')->name('backups.download');
});

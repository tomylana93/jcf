<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\MasterData\UserController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('master-data')->name('master-data.')->group(function (): void {
    Route::inertia('/', 'master-data/Index')->can(Permission::ViewUsers->value)->name('index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('users', [UserController::class, 'store'])->middleware(HandlePrecognitiveRequests::class)->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->whereUuid('user')->middleware(HandlePrecognitiveRequests::class)->name('users.update');
    Route::post('users/{user}/invitation', [UserController::class, 'sendInvitation'])->whereUuid('user')->name('users.invitation');
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->whereUuid('user')->name('users.status');
});

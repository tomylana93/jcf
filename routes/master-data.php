<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\MasterData\UserController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::middleware(['auth', 'verified'])->prefix('master-data')->name('master-data.')->group(function (): void {
    Route::get('/', function (): Response {
        abort_unless(Gate::any([Permission::ViewUsers->value, Permission::ManageBackups->value]), 403);

        return Inertia::render('master-data/Index');
    })->name('index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('users', [UserController::class, 'store'])->middleware(HandlePrecognitiveRequests::class)->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->whereUuid('user')->middleware(HandlePrecognitiveRequests::class)->name('users.update');
    Route::post('users/{user}/invitation', [UserController::class, 'sendInvitation'])->whereUuid('user')->name('users.invitation');
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->whereUuid('user')->name('users.status');
});

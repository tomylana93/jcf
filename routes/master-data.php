<?php

declare(strict_types=1);

use App\Http\Controllers\Identity\ApiClientController;
use App\Http\Controllers\Identity\UserController;
use App\Http\Controllers\MasterDataController;
use App\Support\ApiDomain;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('master-data')->name('master-data.')->group(function (): void {
    Route::get('/', MasterDataController::class)->name('index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('users', [UserController::class, 'store'])->middleware(HandlePrecognitiveRequests::class)->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->whereUuid('user')->middleware(HandlePrecognitiveRequests::class)->name('users.update');
    Route::post('users/{user}/invitation', [UserController::class, 'sendInvitation'])->whereUuid('user')->name('users.invitation');
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->whereUuid('user')->name('users.status');

    if (ApiDomain::enabled()) {
        Route::middleware('feature:api')->group(function (): void {
            Route::get('api-clients', [ApiClientController::class, 'index'])->name('api-clients.index');
            Route::get('api-clients/export', [ApiClientController::class, 'export'])->name('api-clients.export');
            Route::post('api-clients', [ApiClientController::class, 'store'])->name('api-clients.store');
            Route::get('api-clients/{apiClient}', [ApiClientController::class, 'show'])->whereUuid('apiClient')->name('api-clients.show');
            Route::patch('api-clients/{apiClient}', [ApiClientController::class, 'update'])->whereUuid('apiClient')->name('api-clients.update');
            Route::post('api-clients/{apiClient}/tokens', [ApiClientController::class, 'storeToken'])->whereUuid('apiClient')->middleware('throttle:6,1')->name('api-clients.tokens.store');
            Route::delete('api-clients/{apiClient}/tokens/{token}', [ApiClientController::class, 'destroyToken'])->whereUuid('apiClient')->whereNumber('token')->name('api-clients.tokens.destroy');
        });
    }
});

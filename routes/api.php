<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Api\V1\CurrentApiClientController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

Route::get('ping', PingController::class)->name('ping');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('token', [TokenController::class, 'store'])->middleware('throttle:login')->name('token.store');
    Route::post('two-factor', TwoFactorChallengeController::class)->middleware('throttle:api-two-factor')->name('two-factor');
    Route::delete('token', [TokenController::class, 'destroy'])->middleware(['auth:sanctum', 'api.owner:user'])->name('token.destroy');
});

Route::middleware(['auth:sanctum', 'api.owner:user', 'verified'])->group(function (): void {
    Route::get('user', CurrentUserController::class)->middleware('abilities:read')->name('user');
});

Route::middleware(['auth:sanctum', 'api.owner:client'])->prefix('partner')->name('partner.')->group(function (): void {
    Route::get('me', CurrentApiClientController::class)->middleware('abilities:read')->name('me');
});

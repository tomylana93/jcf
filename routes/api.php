<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CurrentApiClientController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

Route::get('ping', PingController::class)->name('ping');

Route::middleware(['auth:sanctum', 'api.owner:user', 'verified'])->group(function (): void {
    Route::get('user', CurrentUserController::class)->middleware('abilities:read')->name('user');
});

Route::middleware(['auth:sanctum', 'api.owner:client'])->prefix('partner')->name('partner.')->group(function (): void {
    Route::get('me', CurrentApiClientController::class)->middleware('abilities:read')->name('me');
});

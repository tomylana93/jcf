<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

Route::get('ping', PingController::class)->name('ping');

Route::middleware(['auth:sanctum', 'api.active', 'verified'])->group(function (): void {
    Route::get('user', CurrentUserController::class)->name('user');
});

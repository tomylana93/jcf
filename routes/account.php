<?php

declare(strict_types=1);

use App\Http\Controllers\Account\ApiTokenController;
use App\Http\Controllers\Account\ProfileAvatarController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SessionController;
use App\Support\ApiDomain;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('account', fn () => to_route('profile.edit'))->name('account');
    Route::get('account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('account/profile/avatar/temporary', [ProfileAvatarController::class, 'upload'])->middleware('throttle:20,1')->name('profile.avatar.upload');
    Route::get('account/profile/avatar/temporary/{temporaryAvatar}', [ProfileAvatarController::class, 'preview'])->whereUuid('temporaryAvatar')->middleware('no-store')->name('profile.avatar.preview');
    Route::delete('account/profile/avatar/temporary', [ProfileAvatarController::class, 'discard'])->name('profile.avatar.discard');
    Route::patch('account/profile/avatar', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::delete('account/profile', [ProfileController::class, 'disable'])->name('profile.disable');
    Route::get('account/security', [SecurityController::class, 'edit'])->middleware(RequirePassword::class)->name('security.edit');
    Route::put('account/password', [SecurityController::class, 'update'])->middleware('throttle:6,1')->name('user-password.update');
    Route::get('account/sessions', [SessionController::class, 'index'])->middleware(RequirePassword::class)->name('sessions.index');
    Route::delete('account/sessions', [SessionController::class, 'destroy'])->middleware('throttle:6,1')->name('sessions.destroy');

    if (ApiDomain::enabled()) {
        Route::middleware('feature:api')->group(function (): void {
            Route::get('account/api-tokens', [ApiTokenController::class, 'index'])->middleware(RequirePassword::class)->name('api-tokens.index');
            Route::post('account/api-tokens', [ApiTokenController::class, 'store'])->middleware(['throttle:6,1', RequirePassword::class])->name('api-tokens.store');
            Route::delete('account/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->whereNumber('token')->middleware(RequirePassword::class)->name('api-tokens.destroy');
        });
    }
});

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');

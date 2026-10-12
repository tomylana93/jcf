<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Settings\AuthSettingsController;
use App\Http\Controllers\Settings\BrandSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\LayoutSettingsController;
use App\Http\Controllers\Settings\MailSettingsController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function (): void {
    Route::inertia('/', 'settings/Index')->can(Permission::ViewSettings->value)->name('index');
    Route::get('general', [GeneralSettingsController::class, 'edit'])->can(Permission::ViewSettings->value)->name('general.edit');
    Route::patch('general', [GeneralSettingsController::class, 'update'])->can(Permission::ViewSettings->value)->can(Permission::ManageSettings->value)->middleware(HandlePrecognitiveRequests::class)->name('general.update');
    Route::get('layout', [LayoutSettingsController::class, 'edit'])->can(Permission::ViewSettings->value)->name('layout.edit');
    Route::patch('layout', [LayoutSettingsController::class, 'update'])->can(Permission::ViewSettings->value)->can(Permission::ManageSettings->value)->middleware(HandlePrecognitiveRequests::class)->name('layout.update');
    Route::get('brand', [BrandSettingsController::class, 'edit'])->can(Permission::ViewSettings->value)->name('brand.edit');
    Route::patch('brand', [BrandSettingsController::class, 'update'])->can(Permission::ViewSettings->value)->can(Permission::ManageSettings->value)->name('brand.update');

    Route::get('auth', [AuthSettingsController::class, 'edit'])->can(Permission::ViewSettings->value)->name('auth.edit');
    Route::patch('auth', [AuthSettingsController::class, 'update'])->can(Permission::ViewSettings->value)->can(Permission::ManageSettings->value)->middleware(HandlePrecognitiveRequests::class)->name('auth.update');
    Route::get('mail', [MailSettingsController::class, 'edit'])->can(Permission::ViewSettings->value)->name('mail.edit');
    Route::patch('mail', [MailSettingsController::class, 'update'])->can(Permission::ViewSettings->value)->can(Permission::ManageSettings->value)->name('mail.update');
    Route::get('mail/preview', [MailSettingsController::class, 'preview'])->can(Permission::ViewSettings->value)->name('mail.preview');
    Route::post('mail/test', [MailSettingsController::class, 'test'])->can(Permission::ViewSettings->value)->can(Permission::ManageSettings->value)->middleware('throttle:6,1')->name('mail.test');
});

<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Settings\BrandSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\LayoutSettingsController;
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
});

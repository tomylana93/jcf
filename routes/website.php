<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Website\WebsiteAreaController;
use App\Http\Controllers\Website\WebsitePageController;
use App\Http\Controllers\Website\WebsitePreviewController;
use App\Http\Controllers\Website\WebsitePreviewImageController;
use App\Http\Controllers\Website\WebsiteSettingsController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('website')->name('website.')->group(function (): void {
    Route::get('/', WebsiteAreaController::class)->name('index');
    Route::get('content/{page}', [WebsitePageController::class, 'edit'])->name('page.edit');
    Route::post('content/{page}/draft', [WebsitePageController::class, 'saveDraft'])->middleware(HandlePrecognitiveRequests::class)->can(Permission::EditWebsiteContent->value)->name('page.save');
    Route::post('content/{page}/publish', [WebsitePageController::class, 'publish'])->can(Permission::PublishWebsiteContent->value)->name('page.publish');
    Route::post('content/{page}/unpublish', [WebsitePageController::class, 'unpublish'])->can(Permission::PublishWebsiteContent->value)->name('page.unpublish');
    Route::post('content/{page}/image', [WebsitePageController::class, 'storeImage'])->can(Permission::EditWebsiteContent->value)->name('page.image.store');

    Route::get('preview/{page}', WebsitePreviewController::class)->name('preview');
    Route::get('preview/images/{image}', WebsitePreviewImageController::class)->whereNumber('image')->name('preview.image');

    Route::get('settings', [WebsiteSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('settings', [WebsiteSettingsController::class, 'update'])->can(Permission::ConfigureWebsite->value)->middleware(HandlePrecognitiveRequests::class)->name('settings.update');
    Route::patch('preset', [WebsiteSettingsController::class, 'applyPreset'])->can(Permission::ConfigureWebsite->value)->name('preset.update');
});

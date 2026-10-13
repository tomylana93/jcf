<?php

declare(strict_types=1);

use App\Http\Controllers\Documentation\DocumentationController;
use App\Http\Controllers\Documentation\ManageDocumentationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('documentation')->name('documentation.')->group(function (): void {
    Route::resource('manage', ManageDocumentationController::class)->parameters(['manage' => 'documentation'])->except('show');
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('{documentation:slug}', [DocumentationController::class, 'show'])->name('show');
});

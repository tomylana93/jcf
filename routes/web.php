<?php

declare(strict_types=1);

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn (GeneralSettings $settings) => $settings->public_pages_enabled ? Inertia::render('public/Home') : to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

require __DIR__.'/account.php';

require __DIR__.'/master-data.php';

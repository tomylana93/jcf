<?php

declare(strict_types=1);

use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Permission\UserPermissionController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IndexingController;
use App\Http\Controllers\Public\WebsiteImageController;
use App\Http\Controllers\Search\GlobalSearchController;
use App\Http\Controllers\StartController;
use App\Http\Controllers\System\SystemController;
use App\Http\Middleware\EnsurePublicPagesEnabled;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', [IndexingController::class, 'robots'])->name('robots');

if (config('features.website')) {
    Route::get('/', HomeController::class)->name('home');
    Route::middleware('feature:website')->group(function (): void {
        Route::get('about', AboutController::class)->middleware(EnsurePublicPagesEnabled::class)->name('about');
        Route::get('sitemap.xml', [IndexingController::class, 'sitemap'])->middleware(EnsurePublicPagesEnabled::class)->name('sitemap');
        Route::get('website/images/{image}', WebsiteImageController::class)->whereNumber('image')->middleware(EnsurePublicPagesEnabled::class)->name('website.image');

        Route::middleware(EnsurePublicPagesEnabled::class)->prefix('articles')->name('articles.')->group(function (): void {
            Route::get('/', [ArticleController::class, 'index'])->name('index');
            Route::get('{slug}', [ArticleController::class, 'show'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('show');
        });

        Route::middleware(EnsurePublicPagesEnabled::class)->group(function (): void {
            Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
            Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
        });
    });
} else {
    Route::get('/', StartController::class)->name('home');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('search', GlobalSearchController::class)->middleware('throttle:60,1')->name('search');
    Route::get('permissions', [UserPermissionController::class, 'index'])->name('permissions.index');
    Route::get('permissions/{user}/edit', [UserPermissionController::class, 'edit'])->whereUuid('user')->name('permissions.edit');
    Route::patch('permissions/{user}', [UserPermissionController::class, 'update'])->whereUuid('user')->name('permissions.update');
    Route::get('system', [SystemController::class, 'index'])->name('system.index');
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'update'])->whereUuid('notification')->name('notifications.read');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

if (config('features.chat')) {
    Route::middleware('feature:chat')->group(__DIR__.'/chat.php');
}

require __DIR__.'/settings.php';

if (config('features.website')) {
    Route::middleware('feature:website')->group(__DIR__.'/website.php');
}

require __DIR__.'/account.php';

require __DIR__.'/master-data.php';

if (config('features.documentation')) {
    Route::middleware('feature:documentation')->group(__DIR__.'/documentation.php');
}

if (config('features.backup')) {
    Route::middleware('feature:backup')->group(__DIR__.'/backups.php');
}

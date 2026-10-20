<?php

declare(strict_types=1);

use App\Http\Controllers\Chat\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('chat')->name('chat.')->group(function (): void {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::get('inbox', [ChatController::class, 'inbox'])->name('inbox');
    Route::get('recipients', [ChatController::class, 'recipients'])->middleware('throttle:30,1,chat-recipients')->name('recipients');
    Route::post('conversations', [ChatController::class, 'store'])->middleware('throttle:10,1,chat-start')->name('store');
    Route::get('{conversation}/messages', [ChatController::class, 'history'])->whereUuid('conversation')->name('history');
    Route::post('{conversation}/messages', [ChatController::class, 'send'])->whereUuid('conversation')->middleware('throttle:60,1,chat-send')->name('send');
    Route::post('{conversation}/typing', [ChatController::class, 'typing'])->whereUuid('conversation')->middleware('throttle:60,1,chat-typing')->name('typing');
    Route::patch('{conversation}/read', [ChatController::class, 'read'])->whereUuid('conversation')->name('read');
});

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Chat\GetChatSummary;
use App\Support\Contracts\ChatSummary;
use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChatSummary::class, GetChatSummary::class);
    }
}

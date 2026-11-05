<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(User::BroadcastChannel.'.{id}', fn (User $user, string $id): bool => (string) $user->getKey() === $id && $user->status === UserStatus::Active);

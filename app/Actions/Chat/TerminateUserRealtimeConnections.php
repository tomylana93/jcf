<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;
use Throwable;

final readonly class TerminateUserRealtimeConnections
{
    public function __construct(private Pusher $pusher) {}

    public function handle(User $user): void
    {
        if (config('broadcasting.default') !== 'reverb' || ! $this->isConfigured()) {
            return;
        }

        try {
            $this->pusher->terminateUserConnections((string) $user->getKey());
        } catch (Throwable $exception) {
            Log::warning('Realtime connection termination failed for a disabled account.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }

    private function isConfigured(): bool
    {
        $connection = config('broadcasting.connections.reverb');

        return is_array($connection)
            && filled($connection['key'] ?? null)
            && filled($connection['secret'] ?? null)
            && filled($connection['app_id'] ?? null);
    }
}

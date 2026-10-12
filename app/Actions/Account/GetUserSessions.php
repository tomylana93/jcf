<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class GetUserSessions
{
    /** @return list<array{device: string, ip_address: string|null, last_active_at: string, current: bool}> */
    public function handle(User $user, string $currentSessionId): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return array_values(DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->timestamp)
            ->orderByDesc('last_activity')->orderBy('id')
            ->get(['id', 'user_agent', 'ip_address', 'last_activity'])
            ->map(/** @param object{id: string, user_agent: string|null, ip_address: string|null, last_activity: int} $session */ fn (object $session): array => [
                'device' => $session->user_agent ?: __('sessions.label.unknown'),
                'ip_address' => $session->ip_address,
                'last_active_at' => CarbonImmutable::createFromTimestamp($session->last_activity)->toIso8601String(),
                'current' => hash_equals($currentSessionId, $session->id),
            ])->all());
    }
}

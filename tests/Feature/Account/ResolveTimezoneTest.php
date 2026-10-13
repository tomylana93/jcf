<?php

declare(strict_types=1);

use App\Actions\Account\ResolveTimezone;
use App\Models\User;

describe('Timezone resolution', function (): void {
    it('prefers the profile timezone over the device', function (): void {
        $user = User::factory()->make(['timezone' => 'Asia/Jakarta']);

        expect(resolve(ResolveTimezone::class)->handle($user, 'Europe/London'))->toBe([
            'current' => 'Asia/Jakarta', 'source' => 'profile',

        ]);
    });

    it('uses the device when the profile timezone is unavailable', function (?string $timezone): void {
        $user = User::factory()->make(['timezone' => $timezone]);

        expect(resolve(ResolveTimezone::class)->handle($user, 'Europe/London'))->toBe([
            'current' => 'Europe/London', 'source' => 'device',

        ]);
    })->with([null, 'invalid']);

    it('falls back to UTC for missing or invalid device timezones', function (mixed $cookie): void {
        expect(resolve(ResolveTimezone::class)->handle(null, $cookie))->toBe([
            'current' => 'UTC', 'source' => 'default',

        ]);
    })->with([null, 'invalid', [['Asia/Jakarta']]]);
});

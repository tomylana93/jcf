<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Session\DatabaseSessionHandler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;

describe('UUID users', function (): void {
    it('persists and retrieves users with UUID identifiers', function (): void {
        $user = User::factory()->create();

        $persistedUser = User::query()->findOrFail($user->id);

        expect($persistedUser->id)->toBeUuid()->toBe($user->id);
    });

    it('stores the authenticated UUID in database sessions', function (): void {
        $user = User::factory()->create();
        actingAs($user);
        $handler = new DatabaseSessionHandler(resolve(ConnectionResolverInterface::class)->connection(), 'sessions', 120, app());

        $handler->write('uuid-user-session', 'session payload');

        assertDatabaseHas('sessions', [
            'id' => 'uuid-user-session',
            'user_id' => $user->id,
        ]);
    });

    it('associates passkeys with UUID users and deletes them with their owner', function (): void {
        $user = User::factory()->create();
        $passkey = $user->passkeys()->create([
            'name' => 'Test passkey',
            'credential_id' => 'test-credential',
            'credential' => [],
        ]);

        expect($passkey->user_id)->toBe($user->id)
            ->and($passkey->user->getKey())->toBe($user->id);

        $user->delete();

        assertModelMissing($passkey);
    });
});

describe('User status', function (): void {
    it('provides localized status options', function (string $locale, string $activeLabel, string $disabledLabel): void {
        app()->setLocale($locale);

        expect(UserStatus::options())->toBe([
            ['value' => 'active', 'label' => $activeLabel, 'name' => 'Active'],
            ['value' => 'disabled', 'label' => $disabledLabel, 'name' => 'Disabled'],
        ]);
    })->with([
        'English' => ['en', 'Active', 'Disabled'],
        'Indonesian' => ['id', 'Aktif', 'Dinonaktifkan'],
    ]);

    it('defaults new users to active', function (): void {
        $user = User::factory()->create();

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });

    it('persists and retrieves enum statuses', function (UserStatus $status, string $storedStatus): void {
        $user = User::factory()->create();
        $user->status = $status;

        $user->save();

        assertDatabaseHas('users', ['id' => $user->id, 'status' => $storedStatus]);
        expect($user->refresh()->status)->toBe($status);
    })->with([
        'active' => [UserStatus::Active, 'active'],
        'disabled' => [UserStatus::Disabled, 'disabled'],
    ]);
});

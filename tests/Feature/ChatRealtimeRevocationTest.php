<?php

declare(strict_types=1);

use App\Actions\Account\DisableAccount;
use App\Actions\MasterData\User\UpdateUser;
use App\Actions\MasterData\User\UpdateUserStatus;
use App\Broadcasting\UserChannelBroadcaster;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use function Pest\Laravel\mock;

describe('Chat realtime revocation', function (): void {
    beforeEach(function (): void {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
    });

    it('includes the authenticated user identifier in private user channel authorization', function (): void {
        $user = User::factory()->create();
        $broadcaster = new UserChannelBroadcaster(new Pusher('test-key', 'test-secret', 'test-app'));
        $request = Request::create('/broadcasting/auth', 'POST', [
            'channel_name' => 'private-App.Models.User.'.$user->id,
            'socket_id' => '123.456',
        ]);
        $request->setUserResolver(fn (): User => $user);

        $response = $broadcaster->validAuthenticationResponse($request, true);

        expect($response)->toHaveKeys(['auth', 'channel_data'])
            ->and(json_decode((string) $response['channel_data'], true))->toBe(['user_id' => $user->id]);
    });

    it('leaves other private channel authorization unchanged', function (): void {
        $broadcaster = new UserChannelBroadcaster(new Pusher('test-key', 'test-secret', 'test-app'));
        $request = Request::create('/broadcasting/auth', 'POST', [
            'channel_name' => 'private-chat.other',
            'socket_id' => '123.456',
        ]);
        $request->setUserResolver(fn (): User => User::factory()->create());

        $response = $broadcaster->validAuthenticationResponse($request, true);

        expect($response)->toHaveKey('auth')
            ->not->toHaveKey('channel_data');
    });

    it('rejects user channel authorization without an authenticated user', function (): void {
        $broadcaster = new UserChannelBroadcaster(new Pusher('test-key', 'test-secret', 'test-app'));
        $request = Request::create('/broadcasting/auth', 'POST', [
            'channel_name' => 'private-App.Models.User.'.User::factory()->create()->id,
            'socket_id' => '123.456',
        ]);
        $request->setUserResolver(fn (): ?User => null);

        expect(fn (): mixed => $broadcaster->validAuthenticationResponse($request, true))
            ->toThrow(AccessDeniedHttpException::class);
    });

    it('terminates active sockets when an account is disabled through every action', function (string $action): void {
        $user = User::factory()->create();
        mock(Pusher::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('terminateUserConnections')->once()->with($user->id)->andReturn((object) []);
        });

        match ($action) {
            'status' => resolve(UpdateUserStatus::class)->handle($user, UserStatus::Disabled),
            'update' => resolve(UpdateUser::class)->handle($user, [
                'name' => $user->name,
                'email' => $user->email,
                'status' => UserStatus::Disabled->value,
            ]),
            'self' => resolve(DisableAccount::class)->handle($user),
        };

        expect($user->refresh()->status)->toBe(UserStatus::Disabled);
    })->with(['status', 'update', 'self']);

    it('does not terminate sockets again for an already disabled account', function (string $action): void {
        $user = User::factory()->disabled()->create();
        mock(Pusher::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('terminateUserConnections');
        });

        match ($action) {
            'status' => resolve(UpdateUserStatus::class)->handle($user, UserStatus::Disabled),
            'update' => resolve(UpdateUser::class)->handle($user, [
                'name' => $user->name,
                'email' => $user->email,
                'status' => UserStatus::Disabled->value,
            ]),
            'self' => resolve(DisableAccount::class)->handle($user),
        };

        expect($user->refresh()->status)->toBe(UserStatus::Disabled);
    })->with(['status', 'update', 'self']);

    it('keeps account disabling working when the realtime termination request fails', function (): void {
        $user = User::factory()->create();
        mock(Pusher::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('terminateUserConnections')->once()->with($user->id)->andThrow(new RuntimeException('Reverb is offline.'));
        });
        Log::spy();

        resolve(DisableAccount::class)->handle($user);

        expect($user->refresh()->status)->toBe(UserStatus::Disabled);
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'Realtime connection termination failed'));
    });

    it('skips termination when the reverb connection is not configured', function (): void {
        config([
            'broadcasting.default' => 'null',
            'broadcasting.connections.reverb.key' => null,
            'broadcasting.connections.reverb.secret' => null,
            'broadcasting.connections.reverb.app_id' => null,
        ]);
        $user = User::factory()->create();
        mock(Pusher::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('terminateUserConnections');
        });

        resolve(DisableAccount::class)->handle($user);

        expect($user->refresh()->status)->toBe(UserStatus::Disabled);
    });
});

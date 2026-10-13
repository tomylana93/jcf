<?php

declare(strict_types=1);
use App\Actions\Account\GetUserSessions;
use App\Actions\Account\RevokeOtherSessions;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

describe('Session management', function (): void {
    it('protects session listing and revocation from guests', function (): void {
        get(route('sessions.index'))->assertRedirect(route('login'));
        delete(route('sessions.destroy'))->assertRedirect(route('login'));
    });
    it('requires confirmation and reports unsupported session listing', function (): void {
        actingAs(User::factory()->create());

        get(route('sessions.index'))->assertRedirect(route('password.confirm'));

        withSession(['auth.password_confirmed_at' => time()]);

        get(route('sessions.index'))->assertInertia(fn (Assert $page) => $page
            ->component('account/Sessions')->where('canListSessions', false)->where('sessions', []));
    });
    it('lists only owned unexpired sessions without exposing session ids or payloads', function (): void {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'current-secret', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Firefox', 'payload' => 'private', 'last_activity' => now()->timestamp],
            ['id' => 'unknown', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => null, 'payload' => 'private', 'last_activity' => now()->timestamp - 1],
            ['id' => 'expired', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => null, 'payload' => '', 'last_activity' => now()->subDay()->timestamp],
            ['id' => 'foreign', 'user_id' => $other->id, 'ip_address' => null, 'user_agent' => 'Other', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $sessions = resolve(GetUserSessions::class)->handle($user, 'current-secret');

        expect($sessions)->toHaveCount(2)
            ->and($sessions[0])->toHaveKeys(['device', 'ip_address', 'last_active_at', 'current'])
            ->and($sessions[0]['device'])->toBe('Firefox')
            ->and($sessions[0]['current'])->toBeTrue()
            ->and($sessions[0])->not->toHaveKeys(['id', 'payload'])
            ->and($sessions[1]['device'])->toBe(__('account.sessions.label.unknown'));

        resolve(RevokeOtherSessions::class)->handle($user, 'current-secret');

        expect(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe(['current-secret'])
            ->and(DB::table('sessions')->where('id', 'foreign')->exists())->toBeTrue();
    });
    it('rejects missing and incorrect passwords', function (): void {
        actingAs(User::factory()->create());

        delete(route('sessions.destroy'))->assertSessionHasErrors(['password' => __('validation.required', ['attribute' => 'password'])]);
        delete(route('sessions.destroy'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    });
    it('preserves the current login while invalidating an older session', function (): void {
        $user = User::factory()->create();

        post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();

        Auth::forgetGuards();

        get(route('dashboard'))->assertOk();

        $oldSession = session()->all();

        delete(route('sessions.destroy'), ['password' => 'password'])->assertSessionHasNoErrors()->assertRedirect();

        Auth::forgetGuards();

        get(route('dashboard'))->assertOk();

        withSession($oldSession);
        Auth::forgetGuards();

        get(route('dashboard'))->assertRedirect(route('login'));
    });
});

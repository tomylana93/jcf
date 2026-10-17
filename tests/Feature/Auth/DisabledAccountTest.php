<?php

declare(strict_types=1);

use App\Actions\Account\DisableAccount;
use App\Actions\MasterData\User\UpdateUser;
use App\Actions\MasterData\User\UpdateUserStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('Disabled accounts', function (): void {
    it('permanently rejects old sessions after disable and reactivate but allows a fresh login', function (string $path): void {
        $user = User::factory()->create();
        post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => true])
            ->assertRedirect(route('dashboard', absolute: false));
        $rememberToken = $user->fresh()->getRememberToken();

        match ($path) {
            'status' => resolve(UpdateUserStatus::class)->handle($user, UserStatus::Disabled),
            'update' => resolve(UpdateUser::class)->handle($user, [
                'name' => $user->name, 'email' => $user->email, 'status' => 'disabled',
            ]),
            'self' => resolve(DisableAccount::class)->handle($user),
        };
        $user->refresh();
        expect($user->getRememberToken())->not->toBe($rememberToken);
        $user->status = UserStatus::Active;
        $user->save();
        Auth::forgetGuards();

        get(route('dashboard'))->assertRedirect(route('login'));
        assertGuest();
        post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        Auth::forgetGuards();
        get(route('dashboard'))->assertOk();
    })->with(['status', 'update', 'self']);

    it('does not disclose account status for an incorrect password', function (): void {
        $user = User::factory()->disabled()->create();

        post(route('login.store'), ['email' => $user->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        assertGuest();
    });

    it('rejects an account disabled while a two-factor challenge is pending', function (): void {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());
        $user = User::factory()->withTwoFactor()->create();

        post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $user->status = UserStatus::Disabled;
        $user->save();

        post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])
            ->assertRedirect(route('login'));

        assertGuest();
    });

    it('revokes authentication established during a request for a disabled account', function (): void {
        $user = User::factory()->disabled()->create();
        Route::middleware('web')->post('/test-authentication', function () use ($user) {
            Auth::login($user);

            return to_route('dashboard');
        });

        post('/test-authentication')->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.disabled')]);

        assertGuest();
    });

    it('explains why login with a valid password is rejected', function (string $locale, string $message): void {
        app()->setLocale($locale);
        $user = User::factory()->create(['status' => UserStatus::Disabled]);
        Event::fake([Login::class]);

        from(route('login'));

        post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => $message]);

        assertGuest();

        Event::assertNotDispatched(Login::class);
    })->with([
        'English' => ['en', 'The account is disabled. Contact an administrator to reactivate the account.'],
        'Indonesian' => ['id', 'Akun dinonaktifkan. Hubungi administrator untuk mengaktifkan kembali akun.'],
    ]);

    it('rejects disabled accounts before starting a two-factor challenge', function (): void {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());
        $user = User::factory()->withTwoFactor()->create(['status' => UserStatus::Disabled]);
        Event::fake([Login::class]);

        from(route('login'));

        post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.disabled')])
            ->assertSessionMissing('login.id');

        assertGuest();

        Event::assertNotDispatched(Login::class);
    });

    it('logs out an existing session before allowing account changes', function (): void {
        $user = User::factory()->create(['status' => UserStatus::Disabled]);
        $originalName = $user->name;

        actingAs($user)->patch(route('profile.update'), ['name' => 'Changed', 'email' => $user->email])
            ->assertRedirect(route('login'));

        assertGuest();
        expect($user->refresh()->name)->toBe($originalName);

        get(route('login'))->assertOk();
    });
});

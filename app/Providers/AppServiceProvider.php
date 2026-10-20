<?php

declare(strict_types=1);

namespace App\Providers;

use App\Broadcasting\UserChannelBroadcaster;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Settings\AuthSettings;
use App\Settings\SettingsMailTransport;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Pusher\Pusher;
use Spatie\Permission\PermissionRegistrar;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(function (): Pusher {
            $connection = config('broadcasting.connections.reverb');
            $options = is_array($connection) && is_array($connection['options'] ?? null) ? $connection['options'] : [];

            return new Pusher(
                (string) (is_array($connection) ? ($connection['key'] ?? '') : ''),
                (string) (is_array($connection) ? ($connection['secret'] ?? '') : ''),
                (string) (is_array($connection) ? ($connection['app_id'] ?? '') : ''),
                ['timeout' => 5, ...$options],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::extend('reverb', fn (Application $app, array $config): UserChannelBroadcaster => new UserChannelBroadcaster(
            resolve(BroadcastManager::class)->pusher($config),
            (bool) ($config['jsonp'] ?? false),
        ));

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && $event->user instanceof User && request()->hasSession()) {
                request()->session()->put('auth.session_token', $event->user->session_token);
            }
        });

        Mail::extend('settings', fn (): SettingsMailTransport => new SettingsMailTransport($this->app->make(MailManager::class)));

        $this->configureDefaults();

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if ($arguments !== []) {
                return null;
            }

            if (Permission::tryFrom($ability) === null && resolve(PermissionRegistrar::class)->getPermissions(['name' => $ability, 'guard_name' => 'web'])->isEmpty()) {
                return null;
            }

            if ($user->status === UserStatus::Disabled) {
                return false;
            }

            if ($ability === Permission::AssignUserPermissions->value) {
                return $user->hasRole(Role::SuperAdmin);
            }

            return $user->hasRole(Role::SuperAdmin) || $user->checkPermissionTo($ability);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function (): Password {
            $rule = Password::min(resolve(AuthSettings::class)->password_min_length);

            return app()->isProduction()
                ? $rule->min(max(12, resolve(AuthSettings::class)->password_min_length))->mixedCase()->letters()->numbers()->symbols()->uncompromised()
                : $rule;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Account\GetUserPermissions;
use App\Actions\Account\ResolveTimezone;
use App\Actions\Chat\GetChatSummary;
use App\Actions\Notification\GetNotificationSummary;
use App\Actions\Settings\GetBrandSettings;
use App\Actions\Settings\GetGeneralSettings;
use App\Actions\Settings\GetLayoutSettings;
use App\Enums\Locale;
use App\Enums\Role;
use App\Settings\AuthSettings;
use App\Support\FeatureManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => [
                'current' => app()->getLocale(),
                'fallback' => config('app.fallback_locale'),
                'options' => Locale::options(),
            ],
            'features' => fn (): array => resolve(FeatureManager::class)->state(),
            'general' => fn (): array => resolve(GetGeneralSettings::class)->handle(),
            'brand' => fn (): array => resolve(GetBrandSettings::class)->handle(),
            'layout' => fn (): array => resolve(GetLayoutSettings::class)->handle(),
            'auth' => [
                'user' => fn () => $request->user()?->append('avatar'),
                'super_admin' => fn (): bool => $request->user()?->hasRole(Role::SuperAdmin) ?? false,
                'permissions' => fn (): array => resolve(GetUserPermissions::class)->handle($request->user()),
            ],
            'publicContext' => [
                'canAccessDashboard' => $request->user() !== null
                    && (! resolve(AuthSettings::class)->email_verification_required || $request->user()->hasVerifiedEmail()),
                'year' => now()->year,
            ],
            'notificationSummary' => fn (): array => resolve(GetNotificationSummary::class)->handle($request->user()),
            'chatSummary' => fn (): array => resolve(FeatureManager::class)->enabled('chat')
                ? resolve(GetChatSummary::class)->handle($request->user())
                : ['unread_count' => 0],
            'timezone' => fn (): array => resolve(ResolveTimezone::class)->handle($request->user(), $request->cookie('timezone')),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

<?php

declare(strict_types=1);

use App\Actions\Settings\UpdateModuleSettings;
use App\Enums\Permission;
use App\Models\User;
use App\Settings\ChatSettings;
use App\Settings\WebsiteSettings;
use App\Support\FeatureManager;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelSettings\SettingsContainer;
use Spatie\LaravelSettings\SettingsEventSubscriber;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('Module settings', function (): void {
    it('requires authentication and settings permissions', function (): void {
        get(route('settings.modules.edit'))->assertRedirect(route('login'));
        patch(route('settings.modules.update'))->assertRedirect(route('login'));
        actingAs(User::factory()->create())->get(route('settings.modules.edit'))->assertForbidden();
        patch(route('settings.modules.update'), ['modules' => ['chat' => false]])->assertForbidden();
    });

    it('allows read access without write access', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings);
        actingAs($user)->get(route('settings.modules.edit'))->assertOk();
        patch(route('settings.modules.update'), ['modules' => ['chat' => false]])->assertForbidden();
    });

    it('hides unavailable modules without resolving their settings', function (): void {
        config(['features.website' => false, 'features.documentation' => false, 'features.backup' => false]);
        foreach (['website', 'documentation', 'backup'] as $module) {
            app()->bind(FeatureManager::SETTINGS[$module], fn () => throw new RuntimeException('Unavailable settings resolved'));
        }
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        actingAs($user)->get(route('settings.modules.edit'))->assertInertia(fn (Assert $page): Assert => $page->component('settings/Modules')->where('modules', ['chat' => true]));
        patch(route('settings.modules.update'), ['modules' => ['chat' => false]])->assertSessionHasNoErrors()->assertRedirect(route('settings.modules.edit'));
        expect(resolve(ChatSettings::class)->refresh()->enabled)->toBeFalse();
    });

    it('returns 404 when every module is hard off', function (): void {
        config(array_fill_keys(['features.website', 'features.chat', 'features.documentation', 'features.backup'], false));
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        actingAs($user)->get(route('settings.modules.edit'))->assertNotFound();
        patch(route('settings.modules.update'), ['modules' => ['chat' => true]])->assertNotFound();
        patch(route('settings.modules.update'), [])->assertNotFound();
    });

    it('rejects unavailable and invalid module values before writing', function (array $modules, string $field): void {
        config(['features.website' => false, 'features.documentation' => false, 'features.backup' => false]);
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        actingAs($user)->patch(route('settings.modules.update'), ['modules' => $modules])->assertInvalid($field);
        expect(resolve(ChatSettings::class)->refresh()->enabled)->toBeTrue();
    })->with([
        'hard off' => [['chat' => false, 'backup' => true], 'modules'],
        'unknown' => [['chat' => false, 'other' => true], 'modules'],
        'invalid boolean' => [['chat' => 'invalid'], 'modules.chat'],
        'missing toggle' => [[], 'modules'],
    ]);

    it('saves each installed module independently of public page availability', function (): void {
        actingAsUserWithPermissions(Permission::ViewSettings, Permission::ManageSettings);
        $modules = ['website' => false, 'chat' => false, 'documentation' => false, 'backup' => false];
        patch(route('settings.modules.update'), ['modules' => $modules])->assertSessionHasNoErrors();
        get(route('settings.modules.edit'))->assertInertia(fn (Assert $page): Assert => $page->where('modules', $modules));
        expect(resolve(WebsiteSettings::class)->refresh()->public_pages_enabled)->toBeTrue();
        foreach (array_keys($modules) as $module) {
            assertDatabaseHas('settings', ['group' => $module, 'name' => 'enabled', 'payload' => 'false']);
            assertDatabaseHas('audit_logs', ['event' => 'settings.updated', 'subject_id' => $module]);
        }
        patch(route('settings.modules.update'), ['modules' => array_fill_keys(array_keys($modules), true)])->assertSessionHasNoErrors();
        get(route('settings.modules.edit'))->assertInertia(fn (Assert $page): Assert => $page->where('modules.website', true)->where('modules.chat', true)->where('modules.documentation', true)->where('modules.backup', true));
    });

    it('normalizes HTTP boolean values before saving', function (): void {
        config(['features.website' => false, 'features.documentation' => false, 'features.backup' => false]);
        actingAsUserWithPermissions(Permission::ViewSettings, Permission::ManageSettings);
        patch(route('settings.modules.update'), ['modules' => ['chat' => '0']])->assertSessionHasNoErrors();
        expect(resolve(ChatSettings::class)->refresh()->enabled)->toBeFalse();
    });

    it('validates direct Action calls', function (): void {
        config(['features.backup' => false]);
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        expect(fn () => resolve(UpdateModuleSettings::class)->handle($user, ['backup' => true]))->toThrow(ValidationException::class);
    });

    it('refreshes soft toggles and audits updates with persistent caching', function (bool $cached): void {
        config(['features.website' => false, 'features.documentation' => false, 'features.backup' => false, 'settings.cache.enabled' => $cached, 'settings.cache.store' => 'file']);
        resolve(SettingsContainer::class)->registerBindings();
        Event::subscribe(new SettingsEventSubscriber(new SettingsCacheFactory(config('settings'))));
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $features = resolve(FeatureManager::class);
        expect($features->enabled('chat'))->toBeTrue();
        actingAs($user)->patch(route('settings.modules.update'), ['modules' => ['chat' => false]])->assertSessionHasNoErrors();
        Event::dispatch(new JobProcessing('sync', new SyncJob(app(), '{}', 'sync', 'default')));
        expect($features->enabled('chat'))->toBeFalse();
        get(route('chat.index'))->assertNotFound();
        assertDatabaseHas('audit_logs', ['event' => 'settings.updated', 'subject_id' => 'chat']);
        patch(route('settings.modules.update'), ['modules' => ['chat' => true]])->assertSessionHasNoErrors();
        expect($features->enabled('chat'))->toBeTrue();
        get(route('chat.index'))->assertForbidden();
    })->with([false, true]);
});

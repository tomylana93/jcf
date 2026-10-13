<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;
use App\Settings\BrandSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelSettings\Events\SavingSettings;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('Brand settings', function (): void {
    it('authorizes brand viewing and mutation independently', function (): void {
        get(route('settings.brand.edit'))->assertRedirect(route('login'));
        patch(route('settings.brand.update'), [])->assertRedirect(route('login'));

        $user = User::factory()->create();
        actingAs($user)->get(route('settings.brand.edit'))->assertForbidden();
        assignRolePermissions($user, Permission::ViewSettings);

        get(route('settings.brand.edit'))->assertOk();
        patch(route('settings.brand.update'), ['color' => 'blue'])->assertForbidden();
    });

    it('persists brand uploads and only deletes replaced assets', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->patch(route('settings.brand.update'), [
            'color' => 'blue', 'logo_type' => 'logo_only',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'favicon' => UploadedFile::fake()->image('icon.png'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('settings.brand.edit'));

        $brand = resolve(BrandSettings::class)->refresh();
        $logo = $brand->logo_path;
        $favicon = $brand->favicon_path;
        Storage::disk('public')->assertExists([$logo, $favicon]);

        patch(route('settings.brand.update'), ['color' => 'green', 'remove_logo' => true])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($logo);
        Storage::disk('public')->assertExists($favicon);
        expect($brand->refresh()->logo_path)->toBeNull()
            ->and($brand->color->value)->toBe('green');
        get(route('settings.brand.edit'))->assertInertia(fn (Assert $page) => $page
            ->where('brand.color', 'green')->where('brand.favicon_url', Storage::disk('public')->url($favicon)));
    });

    it('rejects unsafe uploads and invalid brand choices without persisting', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->patch(route('settings.brand.update'), [
            'color' => 'invalid', 'logo_type' => 'invalid',
            'logo' => UploadedFile::fake()->create('active.svg', 10, 'image/svg+xml'),
        ])->assertInvalid(['color', 'logo_type', 'logo']);

        expect(resolve(BrandSettings::class)->refresh()->color->value)->toBe('neutral');
    });

    it('rejects brand images that cannot be stored', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $source = UploadedFile::fake()->image('logo.png');
        $logo = new class($source->getPathname(), 'logo.png', 'image/png', null, true) extends UploadedFile
        {
            public function store($path = '', $options = []): string|false
            {
                return false;
            }
        };

        actingAs($user)->post(route('settings.brand.update'), [
            '_method' => 'patch',
            'color' => 'blue',
            'logo' => $logo,
        ])->assertSessionHasErrors('logo');
    });

    it('retains the previous brand and cleans new uploads when persistence fails', function (): void {
        Storage::fake('public');
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $settings = resolve(BrandSettings::class);
        $settings->logo_path = UploadedFile::fake()->image('old.png')->store('brand', 'public');
        $settings->save();
        $oldPath = $settings->logo_path;
        Exceptions::fake();
        Event::listen(SavingSettings::class, function (SavingSettings $event): void {
            throw_if($event->settings instanceof BrandSettings, RuntimeException::class, 'Settings storage unavailable');
        });

        actingAs($user)->patch(route('settings.brand.update'), [
            'color' => 'blue', 'logo' => UploadedFile::fake()->image('new.png'),
        ])->assertServerError();

        Exceptions::assertReported(RuntimeException::class);
        expect($settings->refresh()->logo_path)->toBe($oldPath)
            ->and($settings->color->value)->toBe('neutral')
            ->and(Storage::disk('public')->allFiles('brand'))->toBe([$oldPath]);
    });
});

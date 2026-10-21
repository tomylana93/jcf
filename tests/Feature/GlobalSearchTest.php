<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Documentation;
use App\Models\Role;
use App\Models\User;
use App\Settings\DocumentationSettings;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

describe('Global search', function (): void {
    it('requires authentication and verification', function (): void {
        get(route('search', ['query' => 'guide']))->assertRedirect(route('login'));

        actingAs(User::factory()->unverified()->create());

        get(route('search', ['query' => 'guide']))->assertRedirect(route('verification.notice'));
    });

    it('validates query input with 422', function (mixed $query): void {
        actingAs(User::factory()->create());
        getJson(route('search', ['query' => $query]))->assertUnprocessable()->assertJsonValidationErrors('query');
    })->with([null, 'a', '  a  ', str_repeat('a', 201), [['guide']]]);

    it('returns only published full text matches to readers', function (): void {
        $document = Documentation::factory()->published()->create(['title' => 'Mail setup', 'content_text' => 'configuration guide']);
        Documentation::factory()->create(['content_text' => 'configuration guide']);
        actingAs(tap(User::factory()->create(), fn (User $user) => assignRolePermissions($user, Permission::ViewDocumentation)));
        getJson(route('search', ['query' => '  configuration  ']))->assertOk()->assertJsonCount(1, 'documents')->assertJsonPath('documents.0.id', $document->id)->assertJsonPath('users', [])->assertHeader('Cache-Control', 'no-store, private');
    });

    it('returns drafts to managers and caps both result groups at five', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewUsers, Permission::ManageDocumentation);
        Documentation::factory()->count(7)->create(['content_text' => 'uniqueguide']);
        User::factory()->count(7)->create(['name' => 'Uniqueguide Person']);
        actingAs($user);
        getJson(route('search', ['query' => 'uniqueguide']))->assertOk()->assertJsonCount(5, 'documents')->assertJsonCount(5, 'users');
    });

    it('does not query protected data without permissions', function (): void {
        $user = User::factory()->create();
        $user->roles()->detach();
        actingAs($user);
        DB::enableQueryLog();
        getJson(route('search', ['query' => 'guide']))->assertOk()->assertExactJson(['documents' => [], 'users' => []]);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        expect($queries->filter(fn (string $sql): bool => str_contains($sql, 'search_vector') || str_contains($sql, 'ilike')))->toBeEmpty();
    });

    it('matches user email case insensitively without exposing private fields', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewUsers, Permission::ManageDocumentation);
        $target = User::factory()->create(['email' => 'needle@example.com']);
        actingAs($user);
        getJson(route('search', ['query' => 'NEEDLE@']))->assertOk()->assertExactJson(['documents' => [], 'users' => [['id' => $target->id, 'name' => $target->name, 'email' => 'needle@example.com']]]);
        getJson(route('search', ['query' => '%%']))->assertJsonPath('users', []);
        getJson(route('search', ['query' => "' OR 1=1 --"]))->assertJsonPath('users', []);
    });

    it('throttles repeated searches with 429', function (): void {
        actingAs(User::factory()->create());
        for ($attempt = 0; $attempt < 60; $attempt++) {
            getJson(route('search', ['query' => 'guide']))->assertOk();
        }
        getJson(route('search', ['query' => 'guide']))->assertTooManyRequests();
    });

    it('keeps user and document permissions independent', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewUsers);
        $target = User::factory()->create(['name' => 'Needle Person']);
        Documentation::factory()->published()->create(['title' => 'Needle guide']);
        actingAs($user);

        getJson(route('search', ['query' => 'needle']))->assertOk()
            ->assertJsonPath('documents', [])->assertJsonPath('users.0.id', $target->id);
    });

    it('honors direct permissions without requiring a role', function (): void {
        $reader = User::factory()->create();
        $reader->givePermissionTo(App\Models\Permission::findOrCreate(Permission::ViewDocumentation->value, 'web'));
        $document = Documentation::factory()->published()->create(['title' => 'Needle guide']);
        actingAs($reader);

        getJson(route('search', ['query' => 'needle']))->assertOk()
            ->assertJsonPath('documents.0.id', $document->id)->assertJsonPath('users', []);
    });

    it('excludes superadmin targets even when the searcher is superadmin', function (): void {
        $superadmin = User::factory()->create(['name' => 'Needle Superadmin']);
        $superadmin->assignRole(Role::findOrCreate(App\Enums\Role::SuperAdmin->value, 'web'));
        $target = User::factory()->create(['name' => 'Needle Person']);
        $document = Documentation::factory()->create(['title' => 'Needle draft']);
        actingAs($superadmin);

        getJson(route('search', ['query' => 'needle']))->assertOk()
            ->assertJsonCount(1, 'users')->assertJsonPath('users.0.id', $target->id)
            ->assertJsonPath('documents.0.id', $document->id);
    });

    it('blocks disabled accounts even when they have search permissions', function (): void {
        $user = User::factory()->disabled()->create();
        assignRolePermissions($user, Permission::ViewUsers, Permission::ManageDocumentation);
        actingAs($user);

        getJson(route('search', ['query' => 'needle']))->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.disabled')]);
    });

    it('skips documentation queries while the module is inactive', function (bool $hard): void {
        if ($hard) {
            config(['features.documentation' => false]);
        } else {
            resolve(DocumentationSettings::class)->fill(['enabled' => false])->save();
        }

        $resolved = 0;
        app()->beforeResolving(DocumentationSettings::class, function () use (&$resolved): void {
            $resolved++;
        });

        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewDocumentation);
        Documentation::factory()->published()->create(['title' => 'Needle guide', 'content_text' => 'needle']);
        actingAs($user);

        DB::enableQueryLog();
        getJson(route('search', ['query' => 'needle']))->assertOk()->assertJsonPath('documents', []);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        expect($queries->filter(fn (string $sql): bool => str_contains($sql, 'search_vector')))->toBeEmpty();

        if ($hard) {
            expect($resolved)->toBe(0);
        }
    })->with([true, false]);
});

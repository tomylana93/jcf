<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

describe('Settings index', function (): void {
    it('exposes only implemented settings on the settings index', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings);
        actingAs($user)->get(route('settings.index'))->assertInertia(fn (Assert $page) => $page->component('settings/Index'));
    });
});

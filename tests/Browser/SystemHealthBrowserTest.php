<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('System health page', function (): void {
    it('renders deferred health checks after the skeleton without browser errors', function (): void {
        $user = User::factory()->create();
        $user->syncRoles(Role::SuperAdmin);
        actingAs($user);

        visit(route('system.index'))
            ->assertSee(__('system.title'))
            ->assertSee(__('system.check.database'))
            ->assertSee(__('system.check.backup'))
            ->assertNoJavaScriptErrors();
    });
});

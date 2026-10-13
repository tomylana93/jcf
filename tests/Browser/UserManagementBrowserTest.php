<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('User management browser flows', function (): void {
    it('creates and edits users through the sheet and searches the table', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers, Permission::ManageUsers);
        actingAs($actor);

        $page = visit(route('master-data.users.index', absolute: false));

        $page->assertSee(__('navigation.group.admin'))
            ->assertMissing('[data-sidebar="menu-sub"]')
            ->click('[data-sidebar="menu-button"][href="'.route('master-data.index', absolute: false).'"]')
            ->assertSee(__('master_data.description'))
            ->click('main a[href="'.route('master-data.users.index', absolute: false).'"]')
            ->click(__('common.button.create'))
            ->assertSee(__('user.card.create_title'))
            ->fill('#user-name', 'Browser Created')
            ->fill('#user-email', 'browser-created@example.test')
            ->fill('#user-password', 'initial-password')
            ->fill('#user-password-confirmation', 'initial-password')
            ->click('[role="dialog"] button[type="submit"]')
            ->assertSee(__('user.status.created'))
            ->fill('[aria-label="'.__('table.label.search').'"]', 'browser-created@example.test')
            ->assertDontSee($actor->email)
            ->click('tr:has-text("browser-created@example.test") [aria-label="'.__('table.label.actions').'"]')
            ->click(__('table.button.edit'))
            ->fill('#user-name', 'Browser Updated')
            ->click('[role="dialog"] button[type="submit"]')
            ->assertSee('Browser Updated')
            ->assertDontSee($actor->email)
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'stage-three-users-desktop');

        expect(User::query()->where('email', 'browser-created@example.test')->sole()->name)->toBe('Browser Updated');
    });

    it('renders the read only table on mobile', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers);
        actingAs($actor);

        visit(route('master-data.users.index', absolute: false))->on()->mobile()
            ->assertSee(__('user.card.title'))
            ->assertMissing('button:text-is("'.__('common.button.create').'")')
            ->click('[data-sidebar="trigger"]')
            ->assertSee(__('navigation.group.admin'))
            ->assertMissing('[data-sidebar="menu-sub"]')
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'stage-three-users-mobile');
    });

    it('hides unavailable admin navigation from ordinary accounts', function (): void {
        actingAs(User::factory()->create());

        visit(route('dashboard', absolute: false))
            ->assertMissing('nav a[href="'.route('master-data.index', absolute: false).'"]')
            ->assertMissing('nav a[href="'.route('settings.index', absolute: false).'"]')
            ->assertNoJavaScriptErrors();
    });
});

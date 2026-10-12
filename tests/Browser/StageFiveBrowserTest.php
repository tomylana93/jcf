<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ApplicationNotification;

use function Pest\Laravel\actingAs;

describe('audit and notification browser flows', function (): void {
    it('opens the notification bell and marks personal messages read', function (): void {
        $user = User::factory()->create();
        $user->notify(new ApplicationNotification('account_updated'));
        actingAs($user);
        visit(route('dashboard', absolute: false))
            ->click('[data-test="notification-bell"]')
            ->assertSee(__('notification.message.account_updated'))
            ->click('[data-test="read-all-notifications"]')
            ->click(__('notification.status.unread'))
            ->assertSee(__('notification.helper.empty'))
            ->assertNoJavaScriptErrors();
        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('opens audit details on mobile through authorized navigation', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewAuditLogs);
        AuditLog::factory()->create(['subject_label' => 'Audit browser target']);
        actingAs($user);
        visit(route('audit.index', absolute: false))->on()->mobile()
            ->assertSee('Audit browser target')
            ->click(__('audit.button.detail'))
            ->assertSee('Previous name')->assertSee('Updated name')
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'stage-five-audit-mobile');
    });
});

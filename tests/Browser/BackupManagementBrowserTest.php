<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\RestoreSystemBackup;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

describe('Backup dialogs', function (): void {
    it('validates the upload dialog and cancels or confirms restore from the row action menu', function (): void {
        artisan('init:permission')->assertSuccessful();

        $user = User::factory()->create();
        $user->syncRoles(Role::SuperAdmin);
        $disk = Storage::fake('backups');
        config(['jalara.operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'test-secret');
        Queue::fake([RestoreSystemBackup::class]);
        actingAsWithConfirmedPassword($user);

        $browser = visit(route('master-data.backups.index'))
            ->resize(320, 844)
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
            ->assertScript(<<<'JS'
                (() => {
                    const group = document.querySelector('[data-slot="datatable-toolbar-actions"]');
                    const buttons = [...group.querySelectorAll('button')].map(button => button.getBoundingClientRect());
                    return buttons.length === 2 && buttons.every(button => Math.abs(button.width - group.getBoundingClientRect().width) < 1)
                        && buttons[1].top >= buttons[0].bottom;
                })()
                JS, true)
            ->screenshot(filename: 'backup-toolbar-mobile')
            ->resize(1440, 900)
            ->click(__('backup.button.upload'))
            ->assertVisible('[role="dialog"] #backup-archive')
            ->click('[role="dialog"] button[type="submit"]')
            ->assertSee(__('validation.required', ['attribute' => 'archive']))
            ->keys('[role="dialog"]', 'Escape')
            ->click('tr:has-text("test.zip") button[aria-label="Actions"]')
            ->click(__('backup.button.restore'))
            ->assertSee(__('backup.dialog.restore'))
            ->click(__('common.button.cancel'));

        Queue::assertNothingPushed();

        $browser->click('tr:has-text("test.zip") button[aria-label="Actions"]')
            ->click(__('backup.button.restore'))
            ->click('[role="alertdialog"] button[type="submit"]')
            ->assertSee(__('backup.status.restore_queued'))
            ->resize(390, 844)->assertNoJavaScriptErrors();
        Queue::assertPushed(RestoreSystemBackup::class, fn (RestoreSystemBackup $job): bool => $job->filename === 'test.zip');
        $browser->click('tr:has-text("test.zip") button[aria-label="Actions"]')
            ->click(__('common.button.delete'))
            ->assertSee(__('backup.dialog.delete'))
            ->assertSee(__('backup.helper.delete', ['filename' => 'test.zip']))
            ->click(__('common.button.cancel'));
        $disk->assertExists('jcf/test.zip');
        $browser->click('tr:has-text("test.zip") button[aria-label="Actions"]')
            ->click(__('common.button.delete'))
            ->click('[role="alertdialog"] button[type="submit"]')
            ->assertSee(__('backup.status.deleted'))
            ->assertNoJavaScriptErrors();
        $disk->assertMissing('jcf/test.zip');
    });
});

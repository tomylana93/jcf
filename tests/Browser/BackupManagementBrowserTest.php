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
        config(['operations.backup_enabled' => true, 'backup.backup.password' => 'test-secret']);
        createBackupTestArchive(['db-dumps/postgresql-pgsql.sql' => 'SELECT 1;'], 'test-secret');
        Queue::fake([RestoreSystemBackup::class]);
        actingAsWithConfirmedPassword($user);

        $browser = visit(route('master-data.backups.index'))
            ->click(__('backup.button.upload'))
            ->assertVisible('[role="dialog"] #backup-archive')
            ->click('[role="dialog"] button[type="submit"]')
            ->assertSee(__('validation.required', ['attribute' => 'archive']))
            ->keys('[role="dialog"]', 'Escape')
            ->click('tr:has-text("test.zip") button[aria-label="Actions"]')
            ->click(__('backup.button.restore'))
            ->assertSee(__('backup.card.restore'))
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
            ->assertSee(__('backup.card.delete'))
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

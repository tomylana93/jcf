<?php

declare(strict_types=1);

namespace App\Http\Controllers\MasterData;

use App\Actions\MasterData\Backup\DeleteBackup;
use App\Actions\MasterData\Backup\GetBackupChecksum;
use App\Actions\MasterData\Backup\GetBackupPage;
use App\Actions\MasterData\Backup\UploadBackup;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Jobs\CreateSystemBackup;
use App\Jobs\RestoreSystemBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BackupController extends Controller
{
    public function index(Request $request, GetBackupPage $getBackupPage): Response
    {
        Gate::authorize(Permission::ManageBackups);
        $request->validate(['filter.search' => ['nullable', 'string', 'max:255'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100'], 'sort' => ['nullable', 'in:filename,-filename,created_at,-created_at,size,-size']]);

        return Inertia::render('master-data/backups/Index', [
            'backups' => $getBackupPage->handle($request->string('filter.search')->toString(), $request->string('sort', '-created_at')->toString(), $request->integer('per_page', 10), $request->integer('page', 1)),
            'backupConfigured' => (bool) config('jalara.operations.backup_enabled') && (bool) config('backup.backup.password'),
            'backupStatus' => Cache::get('system.backup_status', 'idle'),
            'restoreStatus' => Cache::store('file')->get('system.restore_status', 'idle'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageBackups);
        throw_unless(config('jalara.operations.backup_enabled') && config('backup.backup.password'), ValidationException::withMessages(['backup' => __('backup.error.not_configured')]));
        dispatch(new CreateSystemBackup($request->user()->id));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.status.queued')]);

        return back();
    }

    public function upload(Request $request, UploadBackup $uploadBackup): RedirectResponse
    {
        Gate::authorize(Permission::ManageBackups);
        throw_unless(config('jalara.operations.backup_enabled') && config('backup.backup.password'), ValidationException::withMessages(['archive' => __('backup.error.not_configured')]));
        $request->validate(['archive' => ['required', 'file', 'mimes:zip', 'extensions:zip', 'max:'.config('jalara.operations.backup_upload_max_kilobytes')]]);
        $archive = $request->file('archive');
        throw_unless($archive instanceof UploadedFile, ValidationException::withMessages(['archive' => __('backup.error.invalid_archive')]));
        $uploadBackup->handle($archive);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.status.uploaded')]);

        return back();
    }

    public function restore(Request $request, string $backup, GetBackupChecksum $getBackupChecksum): RedirectResponse
    {
        Gate::authorize(Permission::ManageBackups);
        throw_unless(config('jalara.operations.backup_enabled') && config('backup.backup.password'), ValidationException::withMessages(['backup' => __('backup.error.not_configured')]));
        $request->validate(['confirmation' => ['required', 'accepted']]);
        abort_unless(Storage::disk('backups')->exists(config('backup.backup.name').'/'.$backup), 404);
        dispatch(new RestoreSystemBackup($backup, $getBackupChecksum->handle($backup), $request->user()->id));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.status.restore_queued')]);

        return back();
    }

    public function destroy(string $backup, DeleteBackup $deleteBackup): RedirectResponse
    {
        Gate::authorize(Permission::ManageBackups);
        $deleteBackup->handle($backup);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.status.deleted')]);

        return back();
    }

    public function download(string $backup): StreamedResponse
    {
        Gate::authorize(Permission::ManageBackups);
        $path = config('backup.backup.name').'/'.$backup;
        abort_unless(Storage::disk('backups')->exists($path), 404);

        return Storage::disk('backups')->download($path, $backup, ['Cache-Control' => 'no-store']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Actions\Operations\Backup\DeleteBackup;
use App\Actions\Operations\Backup\GetBackupPage;
use App\Actions\Operations\Backup\GetBackupPath;
use App\Actions\Operations\Backup\IsBackupConfigured;
use App\Actions\Operations\Backup\QueueBackup;
use App\Actions\Operations\Backup\QueueRestore;
use App\Actions\Operations\Backup\UploadBackup;
use App\Enums\BackupOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\Backup\IndexBackupRequest;
use App\Http\Requests\Operations\Backup\RestoreBackupRequest;
use App\Http\Requests\Operations\Backup\UploadBackupRequest;
use App\Http\Resources\Operations\BackupArchiveResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BackupController extends Controller
{
    public function index(IndexBackupRequest $request, GetBackupPage $getBackupPage, IsBackupConfigured $isBackupConfigured): Response
    {
        return Inertia::render('master-data/backups/Index', [
            'backups' => Inertia::defer(fn (): LengthAwarePaginator => $getBackupPage->handle($request->search(), $request->sort(), $request->perPage(), $request->page())
                ->through(fn (array $backup): array => new BackupArchiveResource($backup)->resolve($request))),
            'backupConfigured' => $isBackupConfigured->handle(),
            'backupStatus' => BackupOperation::Backup->status(),
            'restoreStatus' => BackupOperation::Restore->status(),
        ]);
    }

    public function store(Request $request, QueueBackup $queueBackup): RedirectResponse
    {
        $queueBackup->handle($request->user());
        $this->toastSuccess(__('backup.status.queued'));

        return back();
    }

    public function upload(UploadBackupRequest $request, UploadBackup $uploadBackup): RedirectResponse
    {
        $uploadBackup->handle($request->archive());
        $this->toastSuccess(__('backup.status.uploaded'));

        return back();
    }

    public function restore(RestoreBackupRequest $request, string $backup, QueueRestore $queueRestore): RedirectResponse
    {
        $queueRestore->handle($request->user(), $backup);
        $this->toastSuccess(__('backup.status.restore_queued'));

        return back();
    }

    public function destroy(string $backup, DeleteBackup $deleteBackup): RedirectResponse
    {
        $deleteBackup->handle($backup);
        $this->toastSuccess(__('backup.status.deleted'));

        return back();
    }

    public function download(string $backup, GetBackupPath $getBackupPath): StreamedResponse
    {
        return Storage::disk('backups')->download($getBackupPath->handle($backup, mustExist: true), $backup);
    }
}

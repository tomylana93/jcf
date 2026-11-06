import type { TablePaginator } from './table';

/** Mirrors App\Enums\OperationStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type OperationStatus =
    | 'idle'
    | 'running'
    | 'completed'
    | 'failed'
    | 'skipped';

/** Mirrors App\Http\Resources\Operations\BackupArchiveResource. */
export type BackupArchive = {
    filename: string;
    size: number;
    created_at: string;
};
/** `backups` is a deferred prop and stays undefined until the follow-up request resolves. */
export type BackupPageProps = {
    backups?: TablePaginator<BackupArchive>;
    backupConfigured: boolean;
    backupStatus: OperationStatus;
    restoreStatus: OperationStatus;
};

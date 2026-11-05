import type { TablePaginator } from './table';

/** Mirrors App\Enums\OperationStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type OperationStatus =
    | 'idle'
    | 'running'
    | 'completed'
    | 'failed'
    | 'skipped';

export type BackupArchive = {
    filename: string;
    size: number;
    created_at: string;
};
export type BackupPageProps = {
    backups: TablePaginator<BackupArchive>;
    backupConfigured: boolean;
    backupStatus: OperationStatus;
    restoreStatus: OperationStatus;
};

export type BackupArchive = {
    filename: string;
    size: number;
    created_at: string;
};
export type BackupPageProps = {
    backups: import('./table').TablePaginator<BackupArchive>;
    backupConfigured: boolean;
    backupStatus: 'idle' | 'running' | 'completed' | 'failed';
    restoreStatus: 'idle' | 'running' | 'completed' | 'failed';
};

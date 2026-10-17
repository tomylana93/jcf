import { h } from 'vue';

import type { DataTableColumnDef } from '@/components/datatable';
import type { BackupArchive, TranslationKey } from '@/types';

import BackupActions from './BackupActions.vue';

export function createColumns(
    t: (key: TranslationKey) => string,
    formatDateTime: (timestamp: string) => string,
    disabled: boolean,
    deleteDisabled: boolean,
    onDelete: (backup: BackupArchive) => void,
    onRestore: (backup: BackupArchive) => void,
): DataTableColumnDef<BackupArchive>[] {
    return [
        {
            accessorKey: 'filename',
            header: t('backup.label.filename'),
            cell: ({ row }) =>
                h('span', { class: 'wrap-anywhere' }, row.original.filename),
        },
        {
            accessorKey: 'size',
            header: t('backup.label.size'),
            cell: ({ row }) => `${(row.original.size / 1048576).toFixed(2)} MB`,
        },
        {
            accessorKey: 'created_at',
            header: t('backup.label.created_at'),
            cell: ({ row }) => formatDateTime(row.original.created_at),
        },
        {
            id: 'actions',
            header: t('table.label.actions'),
            enableSorting: false,
            enableHiding: false,
            cell: ({ row }) =>
                h(BackupActions, {
                    backup: row.original,
                    disabled,
                    deleteDisabled,
                    onDelete: () => onDelete(row.original),
                    onRestore: () => onRestore(row.original),
                }),
        },
    ];
}

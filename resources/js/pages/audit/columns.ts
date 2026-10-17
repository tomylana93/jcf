import { h } from 'vue';

import type { DataTableColumnDef } from '@/components/datatable';
import { Button } from '@/components/ui/button';
import type { AuditLogRow, TranslationKey } from '@/types';

export function createColumns(
    t: (key: TranslationKey) => string,
    formatDateTime: (timestamp: string) => string,
    onDetail: (log: AuditLogRow) => void,
): DataTableColumnDef<AuditLogRow>[] {
    return [
        {
            accessorKey: 'created_at',
            header: t('audit.label.time'),
            cell: ({ row }) => formatDateTime(row.original.created_at),
            enableSorting: true,
        },
        {
            accessorKey: 'actor_name',
            header: t('audit.label.actor'),
            enableSorting: false,
        },
        {
            accessorKey: 'event_label',
            header: t('audit.label.event'),
            enableSorting: false,
        },
        {
            accessorKey: 'subject_label',
            header: t('audit.label.subject'),
            enableSorting: false,
        },
        {
            id: 'actions',
            enableHiding: false,
            cell: ({ row }) =>
                h(
                    Button,
                    {
                        variant: 'outline',
                        size: 'sm',
                        onClick: () => onDetail(row.original),
                    },
                    () => t('audit.button.detail'),
                ),
        },
    ];
}

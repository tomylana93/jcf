import { Link } from '@inertiajs/vue3';
import { h } from 'vue';

import type { DataTableColumnDef } from '@/components/datatable';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { show } from '@/routes/master-data/api-clients';
import type {
    ApiClientRow,
    ApiClientStatus,
    DataTableColumnContext,
} from '@/types';

export const apiClientStatusTones = {
    active: 'success',
    disabled: 'neutral',
} as const satisfies Record<ApiClientStatus, 'success' | 'neutral'>;

export function createColumns({
    t,
    formatDateTime,
}: DataTableColumnContext): DataTableColumnDef<ApiClientRow>[] {
    return [
        {
            accessorKey: 'name',
            header: t('api_client.label.name'),
            enableSorting: true,
            cell: ({ row }) =>
                h(
                    Link,
                    {
                        href: show.url(row.original.id),
                        class: 'font-medium underline-offset-4 hover:underline',
                    },
                    () => row.original.name,
                ),
        },
        {
            accessorKey: 'description',
            header: t('api_client.label.description'),
            enableSorting: false,
            cell: ({ row }) =>
                h(
                    'span',
                    { class: 'line-clamp-2 text-muted-foreground' },
                    row.original.description ?? '—',
                ),
        },
        {
            accessorKey: 'tokens_count',
            header: t('api_client.label.tokens'),
            enableSorting: false,
        },
        {
            accessorKey: 'status',
            exportValue: (client) => client.status_label,
            header: t('api_client.label.status'),
            enableSorting: true,
            cell: ({ row }) =>
                h(StatusBadge, {
                    tone: apiClientStatusTones[row.original.status],
                    label: row.original.status_label,
                }),
        },
        {
            accessorKey: 'created_at',
            exportValue: (client) => formatDateTime(client.created_at),
            header: t('api_client.label.created_at'),
            enableSorting: true,
            cell: ({ row }) => formatDateTime(row.original.created_at),
        },
    ];
}

import { Link } from '@inertiajs/vue3';
import { h } from 'vue';

import type { DataTableColumnDef } from '@/components/datatable';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { show } from '@/routes/website/inbox';
import type { TranslationKey } from '@/types';

export type ContactRow = {
    id: string;
    subject: string;
    created_at: string;
    handled_at: string | null;
};
export function createColumns(
    t: (key: TranslationKey) => string,
    formatDateTime: (timestamp: string) => string,
): DataTableColumnDef<ContactRow>[] {
    return [
        {
            accessorKey: 'subject',
            header: t('contact.label.subject'),
            enableSorting: false,
            cell: ({ row }) =>
                h(
                    Link,
                    {
                        href: show(row.original.id).url,
                        class: 'font-medium underline underline-offset-4',
                    },
                    () => row.original.subject,
                ),
        },
        {
            accessorKey: 'created_at',
            header: t('contact.label.received'),
            cell: ({ row }) => formatDateTime(row.original.created_at),
        },
        {
            accessorKey: 'handled_at',
            header: t('contact.label.status'),
            cell: ({ row }) =>
                h(StatusBadge, {
                    label: t(
                        row.original.handled_at
                            ? 'contact.status.handled'
                            : 'contact.status.unhandled',
                    ),
                    tone: row.original.handled_at ? 'success' : 'neutral',
                }),
        },
    ];
}

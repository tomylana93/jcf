import { Link } from '@inertiajs/vue3';
import { h } from 'vue';

import type { DataTableColumnDef } from '@/components/datatable';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { edit } from '@/routes/website/articles';
import type { TranslationKey } from '@/types';
export type ArticleRow = {
    id: string;
    title: string;
    slug: string;
    published: boolean;
    published_at: string | null;
    updated_at: string;
};
export function createColumns(
    t: (key: TranslationKey) => string,
    canEdit: boolean,
): DataTableColumnDef<ArticleRow>[] {
    return [
        {
            accessorKey: 'title',
            enableSorting: false,
            header: t('website.label.title'),
            cell: ({ row }) =>
                canEdit
                    ? h(
                          Link,
                          {
                              href: edit(row.original.id).url,
                              class: 'font-medium underline underline-offset-4',
                          },
                          () => row.original.title,
                      )
                    : row.original.title,
        },
        {
            accessorKey: 'slug',
            enableSorting: false,
            header: t('article.label.slug'),
        },
        {
            id: 'status',
            enableSorting: false,
            header: t('article.label.status'),
            cell: ({ row }) =>
                h(StatusBadge, {
                    label: t(
                        row.original.published
                            ? 'website.status.published'
                            : 'website.status.draft_only',
                    ),
                    tone: row.original.published ? 'success' : 'neutral',
                }),
        },
    ];
}

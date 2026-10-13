import { Link } from '@inertiajs/vue3';
import { h } from 'vue';

import type { DataTableColumnDef } from '@/components/datatable';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { createIdentityColumns } from '@/pages/master-data/users/columns';
import { edit } from '@/routes/permissions';
import type { PermissionUser, TranslationKey } from '@/types';

export function createColumns(
    t: (key: TranslationKey) => string,
): DataTableColumnDef<PermissionUser>[] {
    return [
        ...createIdentityColumns<PermissionUser>(t),
        {
            id: 'direct',
            header: t('permission.label.direct'),
            cell: ({ row }) =>
                row.original.super_admin
                    ? h(Badge, { variant: 'secondary' }, () =>
                          t('permission.helper.all_access'),
                      )
                    : String(row.original.direct_permissions.length),
        },
        {
            id: 'actions',
            enableHiding: false,
            exportable: false,
            cell: ({ row }) =>
                row.original.super_admin
                    ? null
                    : h(
                          Link,
                          {
                              href: edit(row.original.id),
                              class: buttonVariants({
                                  variant: 'outline',
                                  size: 'sm',
                              }),
                          },
                          () => t('permission.card.manage_title'),
                      ),
        },
    ];
}

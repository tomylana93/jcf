import { h } from 'vue';

import { DataTableActions } from '@/components/datatable';
import type { DataTableColumnDef } from '@/components/datatable';
import RoleBadge from '@/components/shared/RoleBadge.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { getInitials } from '@/composables/useInitials';
import type {
    DataTableColumnContext,
    TranslationKey,
    UserRow,
    UserStatus,
} from '@/types';

const statusTones = {
    active: 'success',
    disabled: 'neutral',
} as const satisfies Record<UserStatus, 'success' | 'neutral'>;

export function createColumns({
    t,
    formatDateTime,
    canManage,
    currentUserId,
    onEdit,
    onChangeStatus,
}: DataTableColumnContext & {
    canManage: boolean;
    currentUserId: string;
    onEdit: (user: UserRow) => void;
    onChangeStatus: (user: UserRow) => void;
}): DataTableColumnDef<UserRow>[] {
    return [
        {
            id: 'select',
            exportable: false,
            enableSorting: false,
            enableHiding: false,
            header: ({ table }) =>
                h(Checkbox, {
                    modelValue: table.getIsAllPageRowsSelected()
                        ? true
                        : table.getIsSomePageRowsSelected()
                          ? 'indeterminate'
                          : false,
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                        table.toggleAllPageRowsSelected(value === true),
                    'aria-label': t('table.label.select_visible'),
                    disabled: table.getRowModel().rows.length === 0,
                }),
            cell: ({ row }) =>
                h(Checkbox, {
                    modelValue: row.getIsSelected(),
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                        row.toggleSelected(value === true),
                    'aria-label': t('table.label.select_row'),
                }),
        },
        ...createIdentityColumns<UserRow>(t),
        {
            accessorKey: 'status',
            exportValue: (user) => user.status_label,
            header: t('user.label.status'),
            enableSorting: false,
            cell: ({ row }) =>
                h(StatusBadge, {
                    tone: statusTones[row.original.status],
                    label: row.original.status_label,
                }),
        },
        {
            accessorKey: 'created_at',
            exportValue: (user) => formatDateTime(user.created_at),
            header: t('user.label.created_at'),
            enableSorting: true,
            cell: ({ row }) => formatDateTime(row.original.created_at),
        },
        ...(canManage
            ? [
                  {
                      id: 'actions',
                      exportable: false,
                      enableHiding: false,
                      cell: ({ row }: { row: { original: UserRow } }) =>
                          h(DataTableActions, {
                              recordName: row.original.name,
                              canChangeStatus:
                                  row.original.id !== currentUserId,
                              active: row.original.status === 'active',
                              onEdit: () => onEdit(row.original),
                              onChangeStatus: () =>
                                  onChangeStatus(row.original),
                          }),
                  },
              ]
            : []),
    ];
}

export function createIdentityColumns<TData extends UserRow = UserRow>(
    t: (key: TranslationKey) => string,
): DataTableColumnDef<TData>[] {
    return [
        {
            accessorKey: 'name',
            header: t('user.label.name'),
            enableSorting: true,
            cell: ({ row }) =>
                h('div', { class: 'flex items-center gap-3' }, [
                    h(Avatar, { class: 'size-8 shrink-0' }, () => [
                        row.original.avatar
                            ? h(AvatarImage, {
                                  src: row.original.avatar,
                                  alt: row.original.name,
                              })
                            : null,
                        h(AvatarFallback, {}, () =>
                            getInitials(row.original.name),
                        ),
                    ]),
                    h('span', { class: 'font-medium' }, row.original.name),
                ]),
        },
        {
            accessorKey: 'email',
            header: t('user.label.email'),
            enableSorting: true,
            cell: ({ row }) =>
                row.original.email_verified
                    ? h(TooltipProvider, {}, () =>
                          h(Tooltip, {}, () => [
                              h(TooltipTrigger, { asChild: true }, () =>
                                  h(
                                      Badge,
                                      { variant: 'outline', tabindex: 0 },
                                      () => row.original.email,
                                  ),
                              ),
                              h(TooltipContent, {}, () =>
                                  t('user.helper.email_verified'),
                              ),
                          ]),
                      )
                    : h('span', {}, row.original.email),
        },
        {
            id: 'role',
            exportValue: (user) => user.role.label,
            header: t('user.label.role'),
            cell: ({ row }) => h(RoleBadge, row.original.role),
        },
    ];
}

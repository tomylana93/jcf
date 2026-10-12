import type { Component } from 'vue';

import type { BadgeVariants } from '@/components/ui/badge';
import type { ButtonVariants } from '@/components/ui/button';

export type DataTableFilter = {
    key: string;
    label: string;
    type: 'select' | 'combobox';
    options: Pick<SelectOption, 'value' | 'label'>[];
};

export type DataTableAction = {
    label: string;
    icon?: Component;
    variant?: ButtonVariants['variant'];
    disabled?: boolean;
} & ({ href: string; onClick?: never } | { onClick: () => void; href?: never });

export type TablePaginator<T> = {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
};

export type UserRow = {
    invitation_pending: boolean;
    email_verified: boolean;
    avatar: string;
    id: string;
    name: string;
    email: string;
    status: 'active' | 'disabled';
    status_label: string;
    status_variant: BadgeVariants['variant'];
    created_at: string;
    role: { label: string; color: string };
    roles: {
        id: string;
        name: string;
        label: string;
        color: string;
    }[];
};

export type SelectOption = {
    value: string;
    label: string;
    name: string;
    color?: string;
};

export type DataTableExportValue = string | number | boolean | Date | null;
export type DataTableExportData = {
    headers: string[];
    rows: DataTableExportValue[][];
};

export type DataTableExportMessages = {
    preparing: string;
    ready: string;
    download: string;
    error: string;
};

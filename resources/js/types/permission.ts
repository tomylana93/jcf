import type { UserRow } from '@/types';

export type PermissionOption = { value: string; label: string; group: string };
export type PermissionUser = UserRow & {
    super_admin: boolean;
    direct_permissions: string[];
    inherited_permissions: string[];
};

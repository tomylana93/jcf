import type { UserRow } from '@/types';

/** Mirrors App\Enums\Permission; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type PermissionName =
    | 'chat.use'
    | 'system.view'
    | 'backups.manage'
    | 'audit.view'
    | 'settings.view'
    | 'settings.manage'
    | 'users.view'
    | 'users.manage'
    | 'users.permissions.assign'
    | 'documentation.view'
    | 'documentation.manage'
    | 'api-clients.view'
    | 'api-clients.manage';

export type PermissionOption = {
    value: string;
    label: string;
    group: string;
    group_label: string;
};
export type PermissionLabel = { value: string; label: string };
/** Mirrors App\Http\Resources\Permission\UserPermissionResource. */
export type PermissionUser = UserRow & {
    super_admin: boolean;
    direct_permissions: string[];
    inherited_permissions: PermissionLabel[];
};

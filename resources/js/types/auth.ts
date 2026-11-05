import type { PermissionName, User } from '@/types';

export type Auth = {
    user: User;
    permissions: PermissionName[];
    super_admin?: boolean;
};

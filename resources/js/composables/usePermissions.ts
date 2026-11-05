import { usePage } from '@inertiajs/vue3';

import type { Auth, PermissionName } from '@/types';

export function usePermissions() {
    const page = usePage<{ auth: Auth }>();

    return {
        can: (permission: PermissionName): boolean =>
            page.props.auth.permissions.includes(permission),
    };
}

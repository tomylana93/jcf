import type { User } from '@/types';

export type Auth = {
    user: User;
    permissions: string[];
    super_admin?: boolean;
};

/** Mirrors App\Enums\UserStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type UserStatus = 'active' | 'disabled';

export type User = {
    id: string;
    name: string;
    email: string;
    timezone?: string | null;
    status?: UserStatus;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AvatarSettings = {
    current_url: string;
    temporary: {
        id: string;
        url: string;
    } | null;
};

export type UserRow = {
    invitation_pending: boolean;
    email_verified: boolean;
    avatar: string;
    id: string;
    name: string;
    email: string;
    status: UserStatus;
    status_label: string;
    created_at: string;
    role: { label: string; color: string };
    roles: {
        id: string;
        name: string;
        label: string;
        color: string;
    }[];
};

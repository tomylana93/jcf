export type User = {
    id: string;
    name: string;
    email: string;
    timezone?: string | null;
    status?: 'active' | 'disabled';
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

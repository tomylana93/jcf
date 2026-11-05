export type ApiToken = {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
    expired: boolean;
};

export type ApiTokenForm = {
    name: string;
    abilities: string[];
    expiration: string;
};

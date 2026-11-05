export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at: string;
    last_used_at: string | null;
};

export type ManagePasskeysProps = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

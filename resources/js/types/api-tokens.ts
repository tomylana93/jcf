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

/** Mirrors App\Enums\ApiClientStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type ApiClientStatus = 'active' | 'disabled';

export type ApiClientRow = {
    id: string;
    name: string;
    description: string | null;
    status: ApiClientStatus;
    status_label: string;
    tokens_count: number;
    created_at: string;
};

export type ApiClientForm = {
    name: string;
    description: string;
    status: ApiClientStatus;
};

/** Mirrors App\Enums\ApiClientStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type ApiClientStatus = 'active' | 'disabled';

/** Mirrors App\Http\Resources\Identity\ApiClientResource. */
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

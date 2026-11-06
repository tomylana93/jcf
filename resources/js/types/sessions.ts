/** Mirrors App\Http\Resources\Account\UserSessionResource. */
export type AccountSession = {
    device: string;
    ip_address: string | null;
    last_active_at: string;
    current: boolean;
};

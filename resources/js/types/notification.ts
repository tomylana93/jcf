/** Mirrors App\Enums\NotificationFilter; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type NotificationFilter = 'all' | 'unread' | 'read';

export type NotificationRow = {
    id: string;
    kind: string;
    message: string;
    read_at: string | null;
    created_at: string;
};

export type NotificationSummary = { unread_count: number };

export type BroadcastNotification = {
    id: string;
    kind: string;
    type: string;
};

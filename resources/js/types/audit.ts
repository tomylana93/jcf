export type AuditLogRow = {
    id: string;
    actor_name: string;
    event: string;
    event_label: string;
    subject_label: string;
    before: Record<string, unknown>;
    after: Record<string, unknown>;
    created_at: string;
};

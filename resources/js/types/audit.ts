/** Mirrors App\Actions\Audit\DescribeAuditChanges rows; values are display-ready text. */
export type AuditChange = {
    field: string;
    label: string;
    before: string | null;
    after: string | null;
};

/** Mirrors App\Http\Resources\Audit\AuditLogResource. */
export type AuditLogRow = {
    id: string;
    actor_name: string;
    event: string;
    event_label: string;
    subject_label: string;
    changes: AuditChange[];
    created_at: string;
};

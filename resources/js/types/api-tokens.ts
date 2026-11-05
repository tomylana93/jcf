import type { BadgeVariants } from '@/components/ui/badge';

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

export type ApiClientRow = {
    id: string;
    name: string;
    description: string | null;
    status: 'active' | 'disabled';
    status_label: string;
    status_variant: BadgeVariants['variant'];
    tokens_count: number;
    created_at: string;
};

export type ApiClientForm = {
    name: string;
    description: string;
    status: string;
};

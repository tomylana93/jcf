import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

import type { TranslationKey } from './i18n';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    external?: boolean;
    badge?: number;
    children?: NavItem[];
};

export type NavGroup = {
    title?: string;
    items: NavItem[];
};

export type NavigationCard = {
    id: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
    title: TranslationKey;
    description: TranslationKey;
    dataTest?: string;
};

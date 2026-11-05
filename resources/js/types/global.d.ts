import type { Directive } from 'vue';

import type {
    Auth,
    BrandSettings,
    BreadcrumbItem,
    ChatSummary,
    Features,
    FlashToast,
    GeneralSettings,
    LayoutSettings,
    LocaleSettings,
    NotificationSummary,
    TimezoneSettings,
} from '@/types';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: Auth;
            timezone: TimezoneSettings;
            sidebarOpen: boolean;
            currentYear: number;
            general: GeneralSettings;
            features: Features;
            brand: BrandSettings;
            layout: LayoutSettings;
            locale: LocaleSettings;
            notificationSummary?: NotificationSummary;
            chatSummary?: ChatSummary;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: FlashToast;
            apiToken?: string;
        };
        layoutProps: {
            breadcrumbs: BreadcrumbItem[];
            title: string;
            description: string;
        };
    }
}

declare module 'vue' {
    interface GlobalDirectives {
        vFocus: Directive<HTMLElement, boolean | undefined>;
    }
}

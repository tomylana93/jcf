import type { Directive } from 'vue';

import type {
    Auth,
    BrandSettings,
    ChatSummary,
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
            general: GeneralSettings;
            brand: BrandSettings;
            layout: LayoutSettings;
            locale: LocaleSettings;
            notificationSummary?: NotificationSummary;
            chatSummary?: ChatSummary;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface GlobalDirectives {
        vFocus: Directive<HTMLElement, boolean | undefined>;
    }

    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp } from 'vue';
import { Toaster } from '@/components/ui/sonner';
import { initializeTheme } from '@/composables/useAppearance';
import AccountLayout from '@/layouts/account/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { createTranslationPlugin } from '@/lib/i18n';
import { formatPageTitle } from '@/lib/pageTitle';

void createInertiaApp({
    title: (title, page) => formatPageTitle(title, page),
    layout: (name) => {
        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('account/'):
                return [AppLayout, AccountLayout];
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app, { page, ssr }) => {
        const locale =
            (page.props.locale as { current?: string } | undefined)?.current ??
            'en';

        const fallback =
            (page.props.locale as { fallback?: string } | undefined)
                ?.fallback ?? 'en';
        app.use(createTranslationPlugin(locale, fallback));
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
        if (ssr) {
            return;
        }
        const notifications = document.createElement('div');
        document.body.appendChild(notifications);
        createApp(Toaster, { richColors: false, position: 'top-center' }).mount(
            notifications,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

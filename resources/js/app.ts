import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { createApp, watch } from 'vue';

import { Toaster } from '@/components/ui/sonner';
import { initializeTheme } from '@/composables/useAppearance';
import AccountLayout from '@/layouts/account/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { createTranslationPlugin } from '@/lib/i18n';
import { formatPageTitle } from '@/lib/pageTitle';
import type { BrandSettings, LocaleSettings } from '@/types';

void createInertiaApp<{
    locale?: LocaleSettings;
    brand?: BrandSettings;
}>({
    defaults: {
        visitOptions: (_href, options) => {
            if (!options.method || options.method === 'get') {
                return {};
            }

            return {
                invalidateCacheTags: [
                    ...new Set([
                        ...(typeof options.invalidateCacheTags === 'string'
                            ? [options.invalidateCacheTags]
                            : (options.invalidateCacheTags ?? [])),
                        'navigation',
                    ]),
                ],
            };
        },
    },
    title: (title, page) => formatPageTitle(title, page),
    layout: (name) => {
        switch (true) {
            case name === 'ErrorPage':
                return undefined;
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('account/'):
                return [AppLayout, AccountLayout];
            case name.startsWith('settings/'):
                return AppLayout;
            default:
                return AppLayout;
        }
    },
    withApp: (app, { page, ssr }) => {
        const translations = createTranslationPlugin(
            page.props.locale?.current ?? 'en',
            page.props.locale?.fallback ?? 'en',
        );
        app.use(translations);
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
        const currentPage = usePage<{
            locale?: LocaleSettings;
            brand?: BrandSettings;
        }>();
        watch(
            () => (currentPage.props ?? page.props).locale,
            (locale) => {
                if (!locale) {
                    return;
                }

                translations.global.locale.value = locale.current;
                translations.global.fallbackLocale.value =
                    translations.global.availableLocales.find(
                        (available) => available === locale.fallback,
                    ) ?? 'en';
                document.documentElement.lang = locale.current.replaceAll(
                    '_',
                    '-',
                );
            },
            { immediate: true },
        );
        watch(
            () => (currentPage.props ?? page.props).brand,
            (brand) => {
                document.documentElement.dataset.brandColor =
                    brand?.color ?? 'neutral';
                const icons = Array.from(
                    document.querySelectorAll<HTMLLinkElement>(
                        'link[rel="icon"]',
                    ),
                );
                const icon = icons.shift() ?? document.createElement('link');
                icons.forEach((duplicate) => duplicate.remove());
                icon.rel = 'icon';
                icon.href = brand?.favicon_url ?? '/favicon.svg';
                icon.removeAttribute('sizes');
                icon.removeAttribute('type');
                if (!icon.isConnected) {
                    document.head.appendChild(icon);
                }
            },
            { immediate: true },
        );
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

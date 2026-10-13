import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useTranslations } from '@/composables/useTranslations';
import type { LocaleSettings, TranslationLocale } from '@/types';

export function useLocale() {
    const page = usePage<{ locale?: LocaleSettings }>();

    const { locale: translationLocale, t } = useTranslations();

    const options = computed(
        () =>
            page.props.locale?.options.map((option) => ({
                ...option,
                label: t(`enums.locale.${option.name}`),
            })) ?? [],
    );

    const activeOption = computed(() =>
        options.value.find(
            (option) => option.value === translationLocale.value,
        ),
    );

    const locale = computed(
        () =>
            activeOption.value?.value ??
            page.props.locale?.current ??
            (translationLocale.value as TranslationLocale),
    );

    function updateLocale(value: TranslationLocale): void {
        if (typeof document === 'undefined') {
            return;
        }

        translationLocale.value = value;
        document.documentElement.lang = value;
        document.cookie = `locale=${encodeURIComponent(value)};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
        router.reload({
            preserveErrors: true,
            invalidateCacheTags: ['navigation'],
        });
    }

    return { locale, options, activeOption, updateLocale };
}

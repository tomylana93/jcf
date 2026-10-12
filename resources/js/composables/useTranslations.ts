import { useI18n } from 'vue-i18n';
import type { TranslationKey } from '@/types';

export function useTranslations() {
    const { t, locale } = useI18n({ useScope: 'global' });

    return {
        locale,
        t: (
            key: TranslationKey,
            values: Record<string, string | number> = {},
        ) => t(key, values),
    };
}

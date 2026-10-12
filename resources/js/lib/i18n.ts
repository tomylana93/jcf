import { createI18n } from 'vue-i18n';
import messages from '@/lang/messages.json';
import type { TranslationLocale } from '@/types';

export function createTranslationPlugin(
    locale: string,
    fallbackLocale: string,
) {
    const supportedLocale = (value: string): TranslationLocale => {
        const available = Object.keys(messages).find((key) => key === value);
        return (available ?? 'en') as TranslationLocale;
    };

    return createI18n({
        legacy: false,
        locale: supportedLocale(locale),
        fallbackLocale: supportedLocale(fallbackLocale),
        messages,
    });
}

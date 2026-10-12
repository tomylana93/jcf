import type messages from '@/lang/messages.json';

export type TranslationLocale = keyof typeof messages;
export type TranslationMessages = (typeof messages)[TranslationLocale];

type MessageKeys<T> = {
    [K in keyof T & string]: T[K] extends string
        ? K
        : T[K] extends Record<string, unknown>
          ? `${K}.${MessageKeys<T[K]>}`
          : never;
}[keyof T & string];

export type TranslationKey = MessageKeys<TranslationMessages>;

export type LocaleOption = {
    name: keyof TranslationMessages['enums']['locale'];
    value: TranslationLocale;
    label: string;
};

export type LocaleSettings = {
    current: TranslationLocale;
    fallback: string;
    options: LocaleOption[];
};

import type { InjectionKey, Ref } from 'vue';

export type GlobalSearchResults = {
    documents: {
        id: string;
        title: string;
        summary: string | null;
        slug: string;
    }[];
    users: { id: string; name: string; email: string }[];
};

export const globalSearchKey: InjectionKey<Ref<boolean>> =
    Symbol('globalSearch');

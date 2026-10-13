import { useHttp } from '@inertiajs/vue3';
import { onScopeDispose, ref, watch } from 'vue';

import { search } from '@/routes';
import type { GlobalSearchResults } from '@/types';

export function useGlobalSearch() {
    const query = ref('');
    const results = ref<GlobalSearchResults>({ documents: [], users: [] });
    const loading = ref(false);
    const failed = ref(false);
    const http = useHttp({});
    let timer: ReturnType<typeof setTimeout> | undefined;
    let version = 0;

    function reset(): void {
        version++;
        clearTimeout(timer);
        http.cancel();
        results.value = { documents: [], users: [] };
        loading.value = false;
        failed.value = false;
    }

    function load(): void {
        reset();
        const value = query.value.trim();
        if (Array.from(value).length < 2) return;
        const current = version;
        loading.value = true;
        timer = setTimeout(() => {
            void http
                .get(search.url({ query: { query: value } }), {
                    onSuccess: (response) => {
                        if (version === current)
                            results.value = response as GlobalSearchResults;
                    },
                    onError: () => {
                        if (version === current) failed.value = true;
                    },
                    onFinish: () => {
                        if (version === current) loading.value = false;
                    },
                })
                .catch(() => {
                    if (version === current) {
                        failed.value = true;
                        loading.value = false;
                    }
                });
        }, 300);
    }

    watch(query, load);
    onScopeDispose(reset);
    return { query, results, loading, failed, load, reset };
}

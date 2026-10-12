import { onScopeDispose, ref, watch } from 'vue';

import type { GlobalSearchResults } from '@/types';

// JCF stub: backend search route is not available yet (see jvst routes/search).
// Keeps the same API as jvst so GlobalSearch.vue works, but returns empty results.
export function useGlobalSearch() {
    const query = ref('');

    const results = ref<GlobalSearchResults>({ documents: [], users: [] });

    const loading = ref(false);

    const failed = ref(false);

    function reset(): void {
        results.value = { documents: [], users: [] };
        loading.value = false;
        failed.value = false;
    }

    function load(): void {
        reset();
    }

    watch(query, load);
    onScopeDispose(reset);
    return { query, results, loading, failed, load, reset };
}

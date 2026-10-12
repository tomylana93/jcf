<script setup lang="ts">
import { Search } from '@lucide/vue';
import { inject } from 'vue';

import { Button } from '@/components/ui/button';
import { Kbd } from '@/components/ui/kbd';
import { useTranslations } from '@/composables/useTranslations';
import { globalSearchKey } from '@/types';

const open = inject(globalSearchKey);

const { t } = useTranslations();
function openSearch(): void {
    if (open) open.value = true;
}
</script>

<template>
    <Button
        variant="ghost"
        type="button"
        :aria-label="t('search.label.open')"
        data-test="global-search-trigger"
        @click="openSearch"
    >
        <Search />
        <span class="hidden lg:inline">{{ t('search.title') }}</span>
        <Kbd class="hidden lg:inline-flex">Ctrl/⌘ K</Kbd>
    </Button>
</template>

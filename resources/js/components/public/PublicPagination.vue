<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { PublicNavigationItem } from '@/types';

import PublicLink from './PublicLink.vue';

defineProps<{ items: PublicNavigationItem[] }>();
const { t } = useTranslations();
</script>

<template>
    <nav
        v-if="items.length > 1"
        :aria-label="t('public.label.pagination')"
        class="flex flex-wrap gap-2"
    >
        <PublicLink
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            :aria-current="item.active ? 'page' : undefined"
            :class="[
                'inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-border px-3 text-sm wrap-anywhere hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                item.active && 'bg-muted font-semibold',
            ]"
        >
            {{ item.label }}
        </PublicLink>
    </nav>
</template>

<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { PublicNavigationItem } from '@/types';

import PublicLink from './PublicLink.vue';

defineProps<{ items: PublicNavigationItem[] }>();
const { t } = useTranslations();
</script>

<template>
    <nav
        v-if="items.length"
        :aria-label="t('public.label.breadcrumb')"
    >
        <ol
            class="flex flex-wrap gap-x-3 gap-y-1 text-sm wrap-anywhere text-muted-foreground"
        >
            <li
                v-for="(item, index) in items"
                :key="item.href"
                class="min-w-0"
            >
                <span
                    v-if="index"
                    aria-hidden="true"
                    class="mr-3"
                >
                    /
                </span>
                <span
                    v-if="item.active"
                    aria-current="page"
                >
                    {{ item.label }}
                </span>
                <PublicLink
                    v-else
                    :href="item.href"
                    class="underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
                >
                    {{ item.label }}
                </PublicLink>
            </li>
        </ol>
    </nav>
</template>

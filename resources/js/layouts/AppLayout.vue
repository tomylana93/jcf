<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { provide, ref } from 'vue';

import GlobalSearch from '@/components/shared/GlobalSearch.vue';
import { useBrowserTimezone } from '@/composables/useBrowserTimezone';
import { useRealtimeNotifications } from '@/composables/useRealtimeNotifications';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { type BreadcrumbItem, globalSearchKey } from '@/types';

useBrowserTimezone();

useRealtimeNotifications();

const searchOpen = ref(false);

provide(globalSearchKey, searchOpen);

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <component
        :is="
            usePage().props.layout?.app_layout === 'header'
                ? AppHeaderLayout
                : AppSidebarLayout
        "
        :breadcrumbs="breadcrumbs"
    >
        <slot />
        <GlobalSearch v-model:open="searchOpen" />
    </component>
</template>

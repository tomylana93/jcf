<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import { useBrowserTimezone } from '@/composables/useBrowserTimezone';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

useBrowserTimezone();

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
    </component>
</template>

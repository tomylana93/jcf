<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import AppearanceSwitch from '@/components/shared/AppearanceSwitch.vue';
import LocaleSelector from '@/components/shared/LocaleSelector.vue';
import { useBrowserTimezone } from '@/composables/useBrowserTimezone';
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';

useBrowserTimezone();

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative">
        <div class="absolute top-4 right-4 z-10 flex items-center gap-2">
            <LocaleSelector />
            <AppearanceSwitch />
        </div>
        <component
            :is="
                {
                    card: AuthCardLayout,
                    simple: AuthSimpleLayout,
                    split: AuthSplitLayout,
                }[usePage().props.layout.auth_layout]
            "
            :title="title"
            :description="description"
        >
            <slot />
        </component>
    </div>
</template>

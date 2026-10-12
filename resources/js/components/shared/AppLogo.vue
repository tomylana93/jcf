<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLogoIcon from '@/components/shared/AppLogoIcon.vue';

const page = usePage();
const name = computed(() => page.props.general.site_name);
const showImageOnly = computed(
    () => page.props.brand.logo_type === 'logo_only',
);
</script>

<template>
    <div class="flex min-w-0 items-center gap-2">
        <template v-if="showImageOnly">
            <AppLogoIcon
                class="hidden size-8 shrink-0 group-data-[collapsible=icon]:block"
            />
            <span class="group-data-[collapsible=icon]:hidden">
                <img
                    v-if="page.props.brand.logo_url"
                    :src="page.props.brand.logo_url"
                    :alt="name"
                    class="block h-8 w-40 object-contain object-left"
                />
                <span v-else class="truncate text-sm font-semibold">{{
                    name
                }}</span>
            </span>
        </template>
        <template v-else>
            <AppLogoIcon class="size-8 shrink-0" />
            <span
                class="truncate text-sm font-semibold group-data-[collapsible=icon]:hidden"
                >{{ name }}</span
            >
        </template>
    </div>
</template>

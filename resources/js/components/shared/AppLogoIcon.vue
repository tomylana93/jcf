<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';

const page = usePage();

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <span
        :class="className"
        v-bind="$attrs"
    >
        <img
            v-if="page.props.brand.logo_text_url"
            :src="page.props.brand.logo_text_url"
            :alt="page.props.general.site_name"
            class="block size-full object-contain"
        />
        <img
            v-else
            src="/logo.svg"
            :alt="page.props.general.site_name"
            class="block size-full object-contain dark:hidden"
        />
        <img
            v-if="!page.props.brand.logo_text_url"
            src="/logo-dark.svg"
            :alt="page.props.general.site_name"
            class="hidden size-full object-contain dark:block"
        />
    </span>
</template>

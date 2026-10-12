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
    <span :class="className" v-bind="$attrs">
        <img
            v-if="
                (page.props.brand as { logo_text_url?: string } | undefined)
                    ?.logo_text_url
            "
            :src="
                (page.props.brand as { logo_text_url?: string }).logo_text_url
            "
            :alt="
                (page.props.general as { site_name?: string } | undefined)
                    ?.site_name ?? 'Jalara'
            "
            class="block size-full object-contain"
        />
        <img
            v-else
            src="/logo.svg"
            alt="Jalara"
            class="block size-full object-contain dark:hidden"
        />
        <img
            v-if="
                !(page.props.brand as { logo_text_url?: string } | undefined)
                    ?.logo_text_url
            "
            src="/logo-dark.svg"
            alt="Jalara"
            class="hidden size-full object-contain dark:block"
        />
    </span>
</template>

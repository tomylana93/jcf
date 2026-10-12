<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/shared/AppLogoIcon.vue';

const page = usePage();

const name = computed(
    () =>
        (page.props.general as { site_name?: string } | undefined)?.site_name ??
        (page.props.name as string | undefined) ??
        import.meta.env.VITE_APP_NAME ??
        'Laravel',
);

const showImageOnly = computed(
    () =>
        (page.props.brand as { logo_type?: string } | undefined)?.logo_type ===
        'logo_only',
);

const logoUrl = computed(
    () =>
        (page.props.brand as { logo_url?: string | null } | undefined)
            ?.logo_url ?? null,
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
                    v-if="logoUrl"
                    :src="logoUrl"
                    :alt="name"
                    class="block h-8 w-40 object-contain object-left"
                />
                <template v-else>
                    <img
                        src="/logo-wordmark.svg"
                        :alt="name"
                        class="block h-8 w-40 object-contain object-left dark:hidden"
                    />
                    <img
                        src="/logo-wordmark-dark.svg"
                        :alt="name"
                        class="hidden h-8 w-40 object-contain object-left dark:block"
                    />
                </template>
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

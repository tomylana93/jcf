<script setup lang="ts">
import PublicFooter from '@/components/layout/PublicFooter.vue';
import PublicNavigation from '@/components/layout/PublicNavigation.vue';
import { useBrowserTimezone } from '@/composables/useBrowserTimezone';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

useBrowserTimezone();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <a
            href="#public-content"
            class="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:absolute focus:top-4 focus:left-4"
        >
            {{ t('navigation.label.skip_content') }}
        </a>
        <PublicNavigation />
        <main
            id="public-content"
            tabindex="-1"
            class="mx-auto flex w-full max-w-6xl flex-1 flex-col px-4 py-16 sm:px-6 sm:py-24 lg:px-8"
        >
            <slot />
        </main>
        <PublicFooter />
    </div>
</template>

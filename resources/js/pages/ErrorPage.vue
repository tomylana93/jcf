<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { home } from '@/routes';

const props = defineProps<{ status: number }>();
const { t } = useTranslations();

const error = computed(() => {
    switch (props.status) {
        case 403:
            return {
                title: t('error_page.403.title'),
                description: t('error_page.403.description'),
            };
        case 404:
            return {
                title: t('error_page.404.title'),
                description: t('error_page.404.description'),
            };
        case 419:
            return {
                title: t('error_page.419.title'),
                description: t('error_page.419.description'),
            };
        case 503:
            return {
                title: t('error_page.503.title'),
                description: t('error_page.503.description'),
            };
        default:
            return {
                title: t('error_page.500.title'),
                description: t('error_page.500.description'),
            };
    }
});

const canRetry = computed(() => [419, 500, 503].includes(props.status));

function retry(): void {
    window.location.reload();
}
</script>

<template>
    <main
        class="flex min-h-svh items-center justify-center bg-background px-6 py-12 text-foreground"
    >
        <Head :title="error.title">
            <meta name="robots" content="noindex, nofollow" />
        </Head>

        <div
            class="flex w-full max-w-md flex-col items-center gap-8 text-center"
        >
            <div class="flex flex-col items-center gap-4">
                <span
                    class="rounded-full border border-border px-4 py-1 text-sm font-medium text-muted-foreground"
                    >{{ status }}</span
                >
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ error.title }}
                </h1>
                <p class="max-w-sm text-sm leading-6 text-muted-foreground">
                    {{ error.description }}
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <Button v-if="canRetry" variant="outline" @click="retry">
                    {{ t('error_page.action.retry') }}
                </Button>
                <Button :as="Link" :href="home()">
                    {{ t('error_page.action.home') }}
                </Button>
            </div>
        </div>
    </main>
</template>

<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import PlaceholderPattern from '@/components/shared/PlaceholderPattern.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

const { t } = useTranslations();

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.dashboard'), href: dashboard() }],
    }),
);
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <PageContainer
        :title="t('dashboard.card.title')"
        :description="t('dashboard.card.description')"
    >
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </PageContainer>
</template>

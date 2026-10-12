<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PlaceholderPattern from '@/components/shared/PlaceholderPattern.vue';
import { dashboard } from '@/routes';

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    }),
);
</script>

<template>
    <PageContainer
        title="Dashboard"
        description="Welcome back! Here's an overview of your workspace."
    >
        <Head title="Dashboard" />

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

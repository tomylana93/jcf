<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import AppearanceTabs from '@/components/shared/AppearanceTabs.vue';
import Heading from '@/components/shared/Heading.vue';
import { edit } from '@/routes/appearance';

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    }),
);
</script>

<template>
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Appearance settings"
            description="Update the appearance settings for your account"
        />
        <AppearanceTabs />
    </div>
</template>

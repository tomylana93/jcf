<script setup lang="ts">
import { Head, setLayoutProps, usePoll } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/system';
import type { SystemPageProps } from '@/types';
defineProps<SystemPageProps>();
const { t } = useTranslations();
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('system.title'), href: index() }],
    }),
);
usePoll(15000, { only: ['checks'] });
</script>
<template>
    <PageContainer
        :title="t('system.title')"
        :description="t('system.card.description')"
    >
        <Head :title="t('system.title')" />
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="check in checks"
                :key="check.name"
                class="flex flex-col gap-3 rounded-xl border bg-card p-5"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-medium">
                        {{ t(`system.check.${check.name}`) }}
                    </h2>
                    <Badge
                        :variant="
                            check.status === 'failed'
                                ? 'destructive'
                                : 'secondary'
                        "
                        >{{ t(`system.health.${check.status}`) }}</Badge
                    >
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t(`system.detail.${check.detail}`) }}
                </p>
            </div>
        </div>
        <p class="text-sm text-muted-foreground">
            {{ t('system.helper.mail') }}
        </p>
    </PageContainer>
</template>

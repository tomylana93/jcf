<script setup lang="ts">
import { Head, setLayoutProps, usePoll } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/system';
import type { SystemPageProps } from '@/types';

defineProps<SystemPageProps>();

const { t } = useTranslations();

const statusTones = {
    ok: 'success',
    warning: 'warning',
    failed: 'danger',
} as const;

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('system.title'), href: index() }],
    }),
);

usePoll(15000, { only: ['checks'] });
</script>

<template>
    <Head :title="t('system.title')" />

    <PageContainer
        :title="t('system.title')"
        :description="t('system.card.description')"
    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="check in checks"
                :key="check.name"
                class="gap-3 shadow-none"
            >
                <CardHeader>
                    <CardTitle class="font-medium">
                        {{ t(`system.check.${check.name}`) }}
                    </CardTitle>
                    <CardAction>
                        <StatusBadge
                            :tone="statusTones[check.status]"
                            :label="t(`system.health.${check.status}`)"
                        />
                    </CardAction>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    {{ t(`system.detail.${check.detail}`) }}
                </CardContent>
            </Card>
        </div>
        <p class="text-sm text-muted-foreground">
            {{ t('system.helper.mail') }}
        </p>
    </PageContainer>
</template>

<script setup lang="ts">
import { Deferred, Head, setLayoutProps, usePoll } from '@inertiajs/vue3';
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
import { Skeleton } from '@/components/ui/skeleton';
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
        <Deferred data="checks">
            <template #fallback>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card
                        v-for="placeholder in 7"
                        :key="placeholder"
                        class="gap-3 shadow-none"
                    >
                        <CardHeader>
                            <Skeleton class="h-5 w-32" />
                            <CardAction>
                                <Skeleton class="h-5 w-16 rounded-full" />
                            </CardAction>
                        </CardHeader>
                        <CardContent>
                            <Skeleton class="h-4 w-full" />
                        </CardContent>
                    </Card>
                </div>
            </template>

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
                    <CardContent
                        class="flex flex-col gap-2 text-sm text-muted-foreground"
                    >
                        <p>{{ t(`system.detail.${check.detail}`) }}</p>
                        <p v-if="check.name === 'mail'">
                            {{ t('system.helper.mail') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </Deferred>
    </PageContainer>
</template>

<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';
import { watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import NavigationCardGrid from '@/components/shared/NavigationCardGrid.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/master-data';
import { index as usersIndex } from '@/routes/master-data/users';
import type { NavigationCard } from '@/types';

const { t } = useTranslations();
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.master_data'), href: index() }],
    });
});
const cards: NavigationCard[] = [
    {
        id: 'users',
        href: usersIndex(),
        icon: Users,
        title: 'user.card.title',
        description: 'user.card.description',
    },
];
</script>

<template>
    <PageContainer
        :title="t('navigation.link.master_data')"
        :description="t('master_data.description')"
    >
        <Head :title="t('navigation.link.master_data')" />
        <NavigationCardGrid :cards="cards" />
    </PageContainer>
</template>

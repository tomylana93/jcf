<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Users, DatabaseBackup } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import NavigationCardGrid from '@/components/shared/NavigationCardGrid.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/master-data';
import { index as backupsIndex } from '@/routes/master-data/backups';
import { index as usersIndex } from '@/routes/master-data/users';
import type { NavigationCard } from '@/types';

const { t } = useTranslations();
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.master_data'), href: index() }],
    });
});
const page = usePage();
const cards = computed<NavigationCard[]>(() => [
    ...(page.props.auth.permissions.includes('users.view')
        ? [
              {
                  id: 'users',
                  href: usersIndex(),
                  icon: Users,
                  title: 'user.card.title' as const,
                  description: 'user.card.description' as const,
              },
          ]
        : []),
    ...(page.props.auth.permissions.includes('backups.manage')
        ? [
              {
                  id: 'backups',
                  href: backupsIndex(),
                  icon: DatabaseBackup,
                  title: 'backup.title' as const,
                  description: 'backup.card.description' as const,
              },
          ]
        : []),
]);
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

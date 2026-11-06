<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { DatabaseBackup, KeyRound, Users } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import NavigationCardGrid from '@/components/shared/NavigationCardGrid.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/master-data';
import { index as apiClientsIndex } from '@/routes/master-data/api-clients';
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
const { can } = usePermissions();

const cards = computed<NavigationCard[]>(() => [
    ...(can('users.view')
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
    ...(page.props.features.backup.enabled && can('backups.manage')
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
    ...(page.props.features.api.enabled && can('api-clients.view')
        ? [
              {
                  id: 'api-clients',
                  href: apiClientsIndex(),
                  icon: KeyRound,
                  title: 'api_client.card.title' as const,
                  description: 'api_client.card.description' as const,
              },
          ]
        : []),
]);
</script>

<template>
    <Head :title="t('master_data.title')" />

    <PageContainer
        :title="t('master_data.title')"
        :description="t('master_data.description')"
    >
        <NavigationCardGrid :cards="cards" />
    </PageContainer>
</template>

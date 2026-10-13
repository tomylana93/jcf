<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import {
    LayoutTemplate,
    Mail,
    Palette,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import { watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import NavigationCardGrid from '@/components/shared/NavigationCardGrid.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit as editAuth } from '@/routes/settings/auth';
import { edit as editBrand } from '@/routes/settings/brand';
import { edit } from '@/routes/settings/general';
import { edit as editLayout } from '@/routes/settings/layout';
import { edit as editMail } from '@/routes/settings/mail';
import type { NavigationCard } from '@/types';

const { t } = useTranslations();
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.settings'), href: index() }],
    });
});

const cards: NavigationCard[] = [
    {
        id: 'general',
        href: edit(),
        icon: Settings,
        title: 'settings.general.card.title',
        description: 'settings.general.card.description',
        dataTest: 'general-settings-card',
    },
    {
        id: 'brand',
        href: editBrand(),
        icon: Palette,
        title: 'settings.brand.card.title',
        description: 'settings.brand.card.description',
        dataTest: 'brand-settings-card',
    },
    {
        id: 'auth',
        href: editAuth(),
        icon: ShieldCheck,
        title: 'settings.auth.card.title',
        description: 'settings.auth.card.description',
        dataTest: 'auth-settings-card',
    },
    {
        id: 'layout',
        href: editLayout(),
        icon: LayoutTemplate,
        title: 'settings.layout.card.title',
        description: 'settings.layout.card.description',
        dataTest: 'layout-settings-card',
    },
    {
        id: 'mail',
        href: editMail(),
        icon: Mail,
        title: 'settings.mail.card.title',
        description: 'settings.mail.card.description',
        dataTest: 'mail-settings-card',
    },
];
</script>

<template>
    <PageContainer
        :title="t('settings.card.title')"
        :description="t('settings.card.description')"
    >
        <Head :title="t('settings.title')" />
        <NavigationCardGrid :cards="cards" />
    </PageContainer>
</template>

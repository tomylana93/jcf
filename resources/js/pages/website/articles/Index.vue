<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import { DataTable } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index as websiteIndex } from '@/routes/website';
import { create, index } from '@/routes/website/articles';
import type { DataTableAction } from '@/types';

import { createColumns } from './columns';
import type { ArticleRow } from './columns';
const props = defineProps<{
    articles: {
        data: ArticleRow[];
        meta: { total: number; per_page: number; current_page: number };
    };
}>();
const { t } = useTranslations();
const page = usePage();
const canEdit = computed(
    () =>
        page.props.auth.permissions.includes('website.content.edit') ||
        page.props.auth.permissions.includes('website.content.publish'),
);
const columns = computed(() => createColumns(t, canEdit.value));
const actions = computed<DataTableAction[]>(() =>
    page.props.auth.permissions.includes('website.content.edit')
        ? [
              {
                  label: t('article.button.create'),
                  href: create().url,
                  icon: Plus,
              },
          ]
        : [],
);
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.website'), href: websiteIndex() },
            { title: t('article.title.index'), href: index() },
        ],
    }),
);
</script>
<template>
    <Head :title="t('article.title.index')" />

    <PageContainer
        :title="t('article.title.index')"
        :description="t('article.helper.drafts')"
    >
        <DataTable
            :url="index().url"
            data-key="articles"
            :columns="columns"
            :rows="props.articles.data"
            :row-count="props.articles.meta.total"
            :current-page="props.articles.meta.current_page"
            :page-size="props.articles.meta.per_page"
            :actions="actions"
        />
    </PageContainer>
</template>

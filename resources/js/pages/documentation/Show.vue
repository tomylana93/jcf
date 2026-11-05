<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import RichTextContent from '@/components/editor/RichTextContent.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { buttonVariants } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { index, show } from '@/routes/documentation';
import { edit } from '@/routes/documentation/manage';
import type { ReadableDocumentation } from '@/types';

const props = defineProps<{ document: ReadableDocumentation }>();

const { can } = usePermissions();
const { t } = useTranslations();

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.documentation'), href: index() },
            { title: props.document.title, href: show(props.document.slug) },
        ],
    }),
);
</script>
<template>
    <Head :title="document.title" />

    <PageContainer
        :title="document.title"
        :description="document.summary ?? undefined"
    >
        <div class="flex items-center gap-2">
            <Link
                :href="index()"
                :class="buttonVariants({ variant: 'outline' })"
            >
                {{ t('documentation.button.back') }}
            </Link>
            <Link
                v-if="can('documentation.manage')"
                :href="edit(document.id)"
                :class="buttonVariants()"
            >
                {{ t('documentation.button.edit') }}
            </Link>
            <StatusBadge
                v-if="document.status === 'draft'"
                :label="t('documentation.status.draft')"
            />
        </div>
        <article class="w-full min-w-0">
            <RichTextContent :html="document.content_html" />
        </article>
    </PageContainer>
</template>

<script setup lang="ts">
import { Head, Link, setLayoutProps, usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import RichTextContent from '@/components/editor/RichTextContent.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { index, show } from '@/routes/documentation';
import { edit } from '@/routes/documentation/manage';
import type { ReadableDocumentation } from '@/types';

const props = defineProps<{ document: ReadableDocumentation }>();

const page = usePage();
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
                v-if="
                    page.props.auth.permissions.includes('documentation.manage')
                "
                :href="edit(document.id)"
                :class="buttonVariants()"
            >
                {{ t('documentation.button.edit') }}
            </Link>
            <Badge
                v-if="document.status === 'draft'"
                variant="secondary"
            >
                {{ t('documentation.status.draft') }}
            </Badge>
        </div>
        <article class="w-full min-w-0">
            <RichTextContent :html="document.content_html" />
        </article>
    </PageContainer>
</template>

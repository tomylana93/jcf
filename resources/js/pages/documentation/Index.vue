<script setup lang="ts">
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { ref, watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import SimplePagination from '@/components/shared/SimplePagination.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Empty, EmptyHeader, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { index, show } from '@/routes/documentation';
import {
    create,
    edit,
    index as manageIndex,
} from '@/routes/documentation/manage';
import type { DocumentationPaginator, DocumentationStatus } from '@/types';

const props = defineProps<{
    documents: DocumentationPaginator;
    search: string;
    manage: boolean;
}>();

const { t } = useTranslations();
const page = usePage();

const statusTones = {
    published: 'success',
    draft: 'neutral',
} as const satisfies Record<DocumentationStatus, 'success' | 'neutral'>;

const query = ref(props.search);

function searchDocuments(): void {
    router.get(
        props.manage ? manageIndex.url() : index.url(),
        { search: query.value },
        { preserveState: true },
    );
}

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('breadcrumb.documentation'),
                href: props.manage ? manageIndex() : index(),
            },
        ],
    }),
);
</script>
<template>
    <Head :title="t('documentation.title')" />

    <PageContainer
        :title="
            manage
                ? t('documentation.card.manage_title')
                : t('documentation.title')
        "
        :description="t('documentation.card.description')"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <form
                novalidate
                class="flex flex-1 items-center gap-2"
                @submit.prevent="searchDocuments()"
            >
                <Input
                    v-model="query"
                    type="search"
                    :aria-label="t('documentation.label.search')"
                    :placeholder="t('documentation.placeholder.search')"
                    class="max-w-md"
                />
                <Button
                    type="submit"
                    variant="outline"
                >
                    {{ t('documentation.button.search') }}
                </Button>
            </form>
            <Link
                v-if="manage"
                :href="create()"
                :class="buttonVariants()"
            >
                {{ t('common.button.create') }}
            </Link>
            <Link
                v-else-if="
                    page.props.auth.permissions.includes('documentation.manage')
                "
                :href="manageIndex()"
                :class="buttonVariants({ variant: 'outline' })"
            >
                {{ t('documentation.button.manage') }}
            </Link>
        </div>
        <Empty v-if="!documents.data.length">
            <EmptyHeader>
                <EmptyTitle>
                    {{ t('documentation.helper.empty') }}
                </EmptyTitle>
            </EmptyHeader>
        </Empty>
        <div
            v-else
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Card
                v-for="document in documents.data"
                :key="document.id"
            >
                <CardHeader>
                    <div class="flex items-start justify-between gap-2">
                        <CardTitle>{{ document.title }}</CardTitle>
                        <StatusBadge
                            v-if="manage"
                            :tone="statusTones[document.status]"
                            :label="
                                t(`documentation.status.${document.status}`)
                            "
                        />
                    </div>
                    <CardDescription>
                        {{ document.summary }}
                    </CardDescription>
                </CardHeader>
                <CardFooter class="mt-auto flex gap-2">
                    <Link
                        :href="show(document.slug)"
                        :class="
                            buttonVariants({
                                variant: 'outline',
                                size: 'sm',
                            })
                        "
                    >
                        {{ t('documentation.button.read') }}
                    </Link>
                    <Link
                        v-if="manage"
                        :href="edit(document.id)"
                        :class="buttonVariants({ size: 'sm' })"
                    >
                        {{ t('documentation.button.edit') }}
                    </Link>
                </CardFooter>
            </Card>
        </div>
        <SimplePagination
            :paginator="documents"
            :label="t('documentation.label.pagination')"
            :page-href="
                (pageNumber) =>
                    (manage ? manageIndex : index)({
                        query: { search, page: pageNumber },
                    })
            "
        />
    </PageContainer>
</template>

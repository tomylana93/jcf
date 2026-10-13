<script setup lang="ts">
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { ref, watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardFooter,
} from '@/components/ui/card';
import { Empty, EmptyHeader, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { index, show } from '@/routes/documentation';
import {
    index as manageIndex,
    create,
    edit,
} from '@/routes/documentation/manage';
import type { DocumentationPaginator } from '@/types';
const props = defineProps<{
    documents: DocumentationPaginator;
    search: string;
    manage: boolean;
}>();
const { t } = useTranslations();
const page = usePage();
const query = ref(props.search);
function searchDocuments(pageNumber = 1): void {
    router.get(
        props.manage ? manageIndex.url() : index.url(),
        { search: query.value, page: pageNumber },
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
    <PageContainer
        :title="
            manage
                ? t('documentation.card.manage_title')
                : t('documentation.title')
        "
        :description="t('documentation.card.description')"
    >
        <Head :title="t('documentation.title')" />
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
                /><Button type="submit" variant="outline">{{
                    t('documentation.button.search')
                }}</Button>
            </form>
            <Link v-if="manage" :href="create()" :class="buttonVariants()">{{
                t('common.button.create')
            }}</Link>
            <Link
                v-else-if="
                    page.props.auth.permissions.includes('documentation.manage')
                "
                :href="manageIndex()"
                :class="buttonVariants({ variant: 'outline' })"
                >{{ t('documentation.button.manage') }}</Link
            >
        </div>
        <Empty v-if="!documents.data.length"
            ><EmptyHeader
                ><EmptyTitle>{{
                    t('documentation.helper.empty')
                }}</EmptyTitle></EmptyHeader
            ></Empty
        >
        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="document in documents.data" :key="document.id"
                ><CardHeader
                    ><div class="flex items-start justify-between gap-2">
                        <CardTitle>{{ document.title }}</CardTitle
                        ><Badge
                            v-if="manage"
                            :variant="
                                document.status === 'published'
                                    ? 'default'
                                    : 'secondary'
                            "
                            >{{
                                t(
                                    document.status === 'published'
                                        ? 'documentation.status.published'
                                        : 'documentation.status.draft',
                                )
                            }}</Badge
                        >
                    </div>
                    <CardDescription>{{
                        document.summary
                    }}</CardDescription></CardHeader
                ><CardFooter class="mt-auto flex gap-2"
                    ><Link
                        :href="show(document.slug)"
                        :class="
                            buttonVariants({ variant: 'outline', size: 'sm' })
                        "
                        >{{ t('documentation.button.read') }}</Link
                    ><Link
                        v-if="manage"
                        :href="edit(document.id)"
                        :class="buttonVariants({ size: 'sm' })"
                        >{{ t('documentation.button.edit') }}</Link
                    ></CardFooter
                ></Card
            >
        </div>
        <div
            v-if="documents.last_page > 1"
            class="flex items-center justify-between gap-2"
        >
            <Button
                variant="outline"
                :disabled="documents.current_page === 1"
                @click="searchDocuments(documents.current_page - 1)"
                >{{ t('documentation.button.previous') }}</Button
            ><span class="text-sm text-muted-foreground"
                >{{ documents.current_page }} / {{ documents.last_page }}</span
            ><Button
                variant="outline"
                :disabled="documents.current_page === documents.last_page"
                @click="searchDocuments(documents.current_page + 1)"
                >{{ t('documentation.button.next') }}</Button
            >
        </div>
    </PageContainer>
</template>

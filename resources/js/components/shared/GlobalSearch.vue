<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ListboxFilter } from 'reka-ui';
import { computed, onMounted, onUnmounted, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Command,
    CommandGroup,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useGlobalSearch } from '@/composables/useGlobalSearch';
import { useTranslations } from '@/composables/useTranslations';
import { createEditorLinkOptions } from '@/lib/navigation';
import { show } from '@/routes/documentation';
import { index } from '@/routes/master-data/users';

const open = defineModel<boolean>('open', { required: true });

const page = usePage();
const { t } = useTranslations();
const { query, results, loading, failed, load, reset } = useGlobalSearch();

const pages = computed(() =>
    createEditorLinkOptions(t, page.props.auth.permissions)
        .filter((item) =>
            item.label
                .toLocaleLowerCase()
                .includes(query.value.trim().toLocaleLowerCase()),
        )
        .slice(0, 5),
);

const empty = computed(
    () =>
        !pages.value.length &&
        !results.value.documents.length &&
        !results.value.users.length,
);

function navigate(url: string): void {
    open.value = false;
    router.visit(url);
}

function shortcut(event: KeyboardEvent): void {
    if (
        !event.isComposing &&
        !event.repeat &&
        (event.ctrlKey || event.metaKey) &&
        (event.code === 'KeyK' || event.key.toLowerCase() === 'k')
    ) {
        event.preventDefault();
        open.value = !open.value;
    }
}

watch(open, () => {
    reset();
    query.value = '';
});

watch(
    () => [
        page.url,
        page.props.auth.user.id,
        page.props.auth.permissions.join(','),
    ],
    () => {
        open.value = false;
        reset();
        query.value = '';
    },
);

onMounted(() => window.addEventListener('keydown', shortcut));

onUnmounted(() => window.removeEventListener('keydown', shortcut));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="overflow-hidden p-0">
            <DialogHeader class="sr-only">
                <DialogTitle>{{ t('search.title') }}</DialogTitle>
                <DialogDescription>
                    {{ t('search.placeholder.query') }}
                </DialogDescription>
            </DialogHeader>
            <Command>
                <div class="flex items-center gap-2 border-b px-3 pr-12">
                    <Search class="size-4 shrink-0 text-muted-foreground" />
                    <ListboxFilter
                        v-model="query"
                        autofocus
                        :aria-label="t('search.label.query')"
                        :placeholder="t('search.placeholder.query')"
                        data-test="global-search-input"
                        class="h-12 w-full bg-transparent text-sm outline-none"
                    />
                </div>
                <CommandList class="max-h-[60dvh]">
                    <CommandGroup
                        v-if="pages.length"
                        :heading="t('search.label.pages')"
                    >
                        <CommandItem
                            v-for="item in pages"
                            :key="item.href"
                            :value="item.href"
                            @select="navigate(item.href)"
                        >
                            {{ item.label }}
                        </CommandItem>
                    </CommandGroup>
                    <CommandGroup
                        v-if="results.documents.length"
                        :heading="t('search.label.documents')"
                    >
                        <CommandItem
                            v-for="item in results.documents"
                            :key="item.id"
                            :value="item.id"
                            @select="navigate(show.url(item.slug))"
                        >
                            <div class="min-w-0">
                                <div class="truncate">{{ item.title }}</div>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ item.summary }}
                                </p>
                            </div>
                        </CommandItem>
                    </CommandGroup>
                    <CommandGroup
                        v-if="results.users.length"
                        :heading="t('search.label.users')"
                    >
                        <CommandItem
                            v-for="item in results.users"
                            :key="item.id"
                            :value="item.id"
                            @select="
                                navigate(
                                    index.url({
                                        query: { 'filter[search]': item.email },
                                    }),
                                )
                            "
                        >
                            <div class="min-w-0">
                                <div class="truncate">{{ item.name }}</div>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ item.email }}
                                </p>
                            </div>
                        </CommandItem>
                    </CommandGroup>
                    <div
                        v-if="loading"
                        role="status"
                        class="flex items-center justify-center gap-2 p-4 text-sm text-muted-foreground"
                    >
                        <Spinner />
                        {{ t('search.status.loading') }}
                    </div>
                    <div
                        v-else-if="failed"
                        role="alert"
                        class="p-4 text-center text-sm"
                    >
                        <p>{{ t('search.error.failed') }}</p>
                        <Button
                            variant="ghost"
                            @click="load"
                        >
                            {{ t('search.button.retry') }}
                        </Button>
                    </div>
                    <p
                        v-else-if="empty"
                        role="status"
                        class="p-4 text-center text-sm text-muted-foreground"
                    >
                        {{ t('search.status.empty') }}
                    </p>
                </CommandList>
            </Command>
        </DialogContent>
    </Dialog>
</template>

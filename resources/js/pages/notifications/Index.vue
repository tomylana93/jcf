<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Bell, CheckCheck } from '@lucide/vue';
import { watchEffect } from 'vue';

import {
    readAll,
    update,
} from '@/actions/App/Http/Controllers/Notification/NotificationController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/notifications';
import { edit as profileEdit } from '@/routes/profile';
import type { NotificationRow, TablePaginator } from '@/types';

const props = defineProps<{
    notifications: TablePaginator<NotificationRow>;
    status: string;
}>();

const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const form = useForm({});
const allForm = useForm({});

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.notifications'), href: index() }],
    }),
);

function markRead(id: string): void {
    form.submit(update(id), { preserveScroll: true });
}

function markAllRead(): void {
    allForm.submit(readAll(), { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('notification.title')" />

    <PageContainer
        :title="t('notification.card.title')"
        :description="t('notification.card.description')"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav
                class="flex gap-2"
                :aria-label="t('notification.label.filter')"
            >
                <Link
                    v-for="filter in ['all', 'unread', 'read'] as const"
                    :key="filter"
                    :href="index({ query: { status: filter } })"
                    :class="
                        buttonVariants({
                            variant: status === filter ? 'default' : 'outline',
                            size: 'sm',
                        })
                    "
                    :aria-current="status === filter ? 'page' : undefined"
                >
                    {{ t(`notification.status.${filter}`) }}
                </Link>
            </nav>
            <form
                novalidate
                @submit.prevent="markAllRead"
            >
                <Button
                    variant="outline"
                    :disabled="allForm.processing"
                    data-test="read-all-notifications"
                >
                    <CheckCheck
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ t('notification.button.read_all') }}
                </Button>
            </form>
        </div>

        <ul
            v-if="notifications.data.length"
            class="divide-y rounded-xl border bg-card"
        >
            <li
                v-for="notification in notifications.data"
                :key="notification.id"
                class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Bell
                        class="mt-1 size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 space-y-2">
                        <p
                            class="text-sm wrap-break-word"
                            :class="{
                                'font-semibold': !notification.read_at,
                            }"
                        >
                            {{ notification.message }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <time
                                :datetime="notification.created_at"
                                class="text-xs text-muted-foreground"
                            >
                                {{ formatDateTime(notification.created_at) }}
                            </time>
                            <Badge
                                v-if="!notification.read_at"
                                variant="secondary"
                            >
                                {{ t('notification.status.unread') }}
                            </Badge>
                        </div>
                        <Link
                            v-if="
                                [
                                    'account_created',
                                    'account_updated',
                                    'permissions_updated',
                                ].includes(notification.kind)
                            "
                            :href="profileEdit()"
                            class="text-xs text-primary underline underline-offset-4"
                        >
                            {{ t('notification.button.view_account') }}
                        </Link>
                    </div>
                </div>
                <form
                    v-if="!notification.read_at"
                    novalidate
                    @submit.prevent="markRead(notification.id)"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="form.processing"
                        :data-test="`read-notification-${notification.id}`"
                    >
                        {{ t('notification.button.read') }}
                    </Button>
                </form>
            </li>
        </ul>
        <div
            v-else
            class="flex flex-col items-center gap-2 rounded-xl border border-dashed py-16 text-center"
        >
            <Bell
                class="size-8 text-muted-foreground"
                aria-hidden="true"
            />
            <p class="font-medium">{{ t('notification.helper.empty') }}</p>
            <p class="text-sm text-muted-foreground">
                {{ t('notification.helper.empty_description') }}
            </p>
        </div>
        <nav
            class="flex items-center justify-between gap-3"
            :aria-label="t('notification.label.pagination')"
        >
            <Link
                v-if="notifications.current_page > 1"
                :href="
                    index({
                        query: {
                            status,
                            page: notifications.current_page - 1,
                        },
                    })
                "
                :class="buttonVariants({ variant: 'outline', size: 'sm' })"
            >
                {{ t('table.pagination.button.previous') }}
            </Link>
            <span v-else />
            <p class="text-sm text-muted-foreground">
                {{
                    t('table.pagination.helper.page_status', {
                        current: notifications.current_page,
                        total: Math.max(
                            1,
                            Math.ceil(
                                notifications.total / notifications.per_page,
                            ),
                        ),
                    })
                }}
            </p>
            <Link
                v-if="
                    notifications.current_page * notifications.per_page <
                    notifications.total
                "
                :href="
                    index({
                        query: {
                            status,
                            page: notifications.current_page + 1,
                        },
                    })
                "
                :class="buttonVariants({ variant: 'outline', size: 'sm' })"
            >
                {{ t('table.pagination.button.next') }}
            </Link>
            <span v-else />
        </nav>
    </PageContainer>
</template>

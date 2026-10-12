<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

const page = usePage();

const { t } = useTranslations();

const count = computed(
    () =>
        (
            page.props.notificationSummary as
                | { unread_count?: number }
                | undefined
        )?.unread_count ?? 0,
);
</script>

<template>
    <Link
        :href="dashboard()"
        :class="[
            buttonVariants({ variant: 'ghost', size: 'icon' }),
            'relative shrink-0',
        ]"
        :aria-label="t('notification.label.open', { count })"
        data-test="notification-bell"
    >
        <Bell class="size-4" aria-hidden="true" />
        <span
            v-if="count > 0"
            class="absolute -top-1 -right-1 flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
            aria-hidden="true"
        >
            {{ count > 99 ? '99+' : count }}
        </span>
    </Link>
</template>

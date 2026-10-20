<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

defineProps<{ item: NavItem; active?: boolean }>();

const { t } = useTranslations();
</script>

<template>
    <component
        :is="item.external ? 'a' : Link"
        :href="item.external ? toUrl(item.href) : item.href"
        :prefetch="item.external ? undefined : !active"
        :cache-for="item.external ? undefined : '30s'"
        :cache-tags="item.external ? undefined : 'navigation'"
        :target="item.external ? '_blank' : undefined"
        :rel="item.external ? 'noopener noreferrer' : undefined"
        :aria-current="active ? 'page' : undefined"
    >
        <component
            :is="item.icon"
            v-if="item.icon"
            aria-hidden="true"
        />
        <span class="truncate">{{ item.title }}</span>
        <Badge
            v-if="item.badge"
            class="ml-auto shrink-0 group-data-[collapsible=icon]:hidden"
            :aria-label="
                t('navigation.label.unread_messages', { count: item.badge })
            "
        >
            {{ item.badge > 99 ? '99+' : item.badge }}
        </Badge>
        <span
            v-if="item.external"
            class="sr-only"
        >
            {{ t('navigation.helper.opens_new_tab') }}
        </span>
    </component>
</template>

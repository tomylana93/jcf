<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { PublicContact } from '@/types';

import PublicLink from './PublicLink.vue';

defineProps<{ contact: PublicContact }>();
const { t } = useTranslations();
</script>

<template>
    <address
        class="flex min-w-0 flex-col gap-5 leading-7 wrap-anywhere not-italic"
    >
        <a
            v-if="contact.email"
            :href="`mailto:${contact.email}`"
            class="w-fit rounded-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
        >
            {{ contact.email }}
        </a>
        <a
            v-if="contact.phone"
            :href="`tel:${contact.phone}`"
            class="w-fit rounded-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
        >
            {{ contact.phone }}
        </a>
        <p
            v-if="contact.address"
            class="whitespace-pre-line"
        >
            {{ contact.address }}
        </p>
        <p
            v-if="contact.hours"
            class="whitespace-pre-line text-muted-foreground"
        >
            {{ contact.hours }}
        </p>
        <PublicLink
            v-if="contact.location_url"
            :href="contact.location_url"
            class="w-fit rounded-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
        >
            {{ t('public.button.location') }}
        </PublicLink>
        <ul
            v-if="contact.social_links.length"
            class="flex flex-wrap gap-x-6 gap-y-3"
        >
            <li
                v-for="link in contact.social_links"
                :key="link.href"
            >
                <PublicLink
                    :href="link.href"
                    class="rounded-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
                >
                    {{ link.label }}
                </PublicLink>
            </li>
        </ul>
    </address>
</template>

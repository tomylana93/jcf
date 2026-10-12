<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import type { NavigationCard } from '@/types';

defineProps<{ cards: NavigationCard[] }>();

const { t } = useTranslations();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Link
            v-for="card in cards"
            :key="card.id"
            :href="card.href"
            :prefetch="!isCurrentUrl(card.href)"
            cache-for="30s"
            cache-tags="navigation"
            :data-test="card.dataTest"
            class="rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <Card class="h-full transition-colors hover:bg-accent">
                <CardHeader>
                    <component
                        :is="card.icon"
                        class="size-6 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <CardTitle>{{ t(card.title) }}</CardTitle>
                    <CardDescription>{{ t(card.description) }}</CardDescription>
                </CardHeader>
            </Card>
        </Link>
    </div>
</template>

<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { PublicArticle } from '@/types';

import type { PublicPresetDefinition } from './presets';
import PublicArticleCard from './PublicArticleCard.vue';

defineProps<{
    articles: PublicArticle[];
    variant: PublicPresetDefinition['articles'];
    showEmpty?: boolean;
}>();
const { t } = useTranslations();
</script>

<template>
    <div
        v-if="articles.length"
        :class="[
            'grid gap-10',
            variant === 'grid' && 'sm:grid-cols-2 lg:grid-cols-3',
            variant === 'lead' && 'sm:grid-cols-2',
            variant === 'list' &&
                'divide-y divide-border [&>article+article]:pt-8',
        ]"
    >
        <PublicArticleCard
            v-for="(article, index) in articles"
            :key="article.id"
            :article="article"
            :compact="variant === 'list'"
            :prominent="variant === 'lead' && index === 0"
            :class="
                variant === 'lead' &&
                index === 0 &&
                'sm:col-span-2 sm:max-w-4xl'
            "
        />
    </div>
    <p
        v-else-if="showEmpty"
        role="status"
        class="py-8 leading-7 text-muted-foreground"
    >
        {{ t('public.status.no_articles') }}
    </p>
</template>

<script setup lang="ts">
import type { PublicArticle } from '@/types';

import PublicLink from './PublicLink.vue';

defineProps<{
    article: PublicArticle;
    prominent?: boolean;
    compact?: boolean;
    headingLevel?: 2 | 3;
}>();
</script>

<template>
    <article class="flex min-w-0 flex-col gap-4">
        <img
            v-if="article.cover && !compact"
            :src="article.cover.url"
            :alt="article.cover.alt"
            loading="lazy"
            width="960"
            height="640"
            class="aspect-[3/2] w-full rounded-lg bg-muted object-cover"
        />
        <time
            v-if="article.publishedLabel"
            :datetime="article.publishedAt"
            class="text-sm text-muted-foreground"
        >
            {{ article.publishedLabel }}
        </time>
        <component
            :is="`h${headingLevel ?? 3}`"
            :class="[
                'font-semibold tracking-tight text-balance wrap-anywhere',
                prominent ? 'text-3xl sm:text-4xl' : 'text-xl',
            ]"
        >
            <PublicLink
                :href="article.href"
                class="rounded-sm decoration-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
                {{ article.title }}
            </PublicLink>
        </component>
        <p
            v-if="article.excerpt"
            class="max-w-[70ch] leading-7 wrap-anywhere text-muted-foreground"
        >
            {{ article.excerpt }}
        </p>
    </article>
</template>

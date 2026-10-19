<script setup lang="ts">
import { computed } from 'vue';

import type { PublicWebsite } from '@/types';

import { composePublicSections, publicPresets } from './presets';
import PublicArticleList from './PublicArticleList.vue';
import PublicBreadcrumb from './PublicBreadcrumb.vue';
import PublicContactInfo from './PublicContactInfo.vue';
import PublicPagination from './PublicPagination.vue';
import PublicRichText from './PublicRichText.vue';
import PublicSection from './PublicSection.vue';

const props = defineProps<{
    website: PublicWebsite;
    hideContactInfo?: boolean;
}>();
const preset = computed(() => publicPresets[props.website.preset]);
const sections = computed(() =>
    composePublicSections(
        props.website.page.sections,
        preset.value,
        props.website.preferences[props.website.preset],
    ),
);
const leadingHero = computed(() =>
    sections.value[0]?.type === 'hero' ? sections.value[0].id : null,
);
</script>

<template>
    <div
        :data-public-preset="website.preset"
        :data-public-kind="website.page.kind"
        class="flex min-w-0 flex-col gap-12 sm:gap-16 lg:gap-20"
    >
        <PublicBreadcrumb :items="website.breadcrumbs ?? []" />
        <header
            v-if="!leadingHero"
            class="flex min-w-0 flex-col gap-6"
        >
            <h1
                :class="[
                    'max-w-4xl text-4xl leading-tight font-semibold tracking-tight text-balance wrap-anywhere sm:text-5xl',
                    preset.hero === 'editorial' && 'font-serif lg:text-6xl',
                ]"
            >
                {{ website.page.title }}
            </h1>
            <p
                v-if="website.page.article"
                class="flex flex-wrap gap-x-6 gap-y-2 text-sm wrap-anywhere text-muted-foreground"
            >
                <span v-if="website.page.article.author">
                    {{ website.page.article.author }}
                </span>
                <time :datetime="website.page.article.publishedAt">
                    {{ website.page.article.publishedLabel }}
                </time>
            </p>
        </header>
        <img
            v-if="website.page.cover"
            :src="website.page.cover.url"
            :alt="website.page.cover.alt"
            width="1200"
            height="800"
            class="aspect-[3/2] w-full rounded-xl bg-muted object-cover"
        />
        <PublicRichText
            v-if="website.page.body"
            :html="website.page.body"
        />
        <PublicSection
            v-for="section in sections"
            :key="section.id"
            :section="section"
            :preset="preset"
            :articles="
                section.type === 'articles' && section.featured
                    ? (website.featuredArticles ?? website.articles ?? [])
                    : (website.articles ?? [])
            "
            :primary="section.id === leadingHero"
        />
        <template v-if="website.page.kind === 'article-index'">
            <PublicArticleList
                :heading-level="2"
                :articles="website.articles ?? []"
                :variant="preset.articles"
                show-empty
            />
            <PublicPagination :items="website.pagination ?? []" />
        </template>
        <PublicContactInfo
            v-if="
                !hideContactInfo &&
                website.page.kind === 'contact' &&
                website.contact
            "
            :contact="website.contact"
        />
        <slot />
    </div>
</template>

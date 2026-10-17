<script setup lang="ts">
import { computed } from 'vue';

import type { PublicArticle, PublicSection } from '@/types';

import type { PublicPresetDefinition } from './presets';
import PublicArticleList from './PublicArticleList.vue';
import PublicCta from './PublicCta.vue';
import PublicHeading from './PublicHeading.vue';
import PublicHero from './PublicHero.vue';
import PublicRichText from './PublicRichText.vue';

const props = defineProps<{
    section: PublicSection;
    preset: PublicPresetDefinition;
    articles: PublicArticle[];
    primary?: boolean;
}>();
const articles = computed(() => {
    const section = props.section;
    return section.type === 'articles'
        ? props.articles
              .filter((article) => !section.featured || article.featured)
              .slice(0, section.limit)
        : [];
});
const hasContent = computed(() => {
    const section = props.section;
    if (section.type === 'articles') return articles.value.length > 0;
    if (section.type === 'services' || section.type === 'strengths')
        return section.items.length > 0;
    if (section.type === 'introduction') return section.body.trim().length > 0;
    return true;
});
</script>

<template>
    <section
        v-if="hasContent"
        :id="section.id"
        :aria-labelledby="`${section.id}-title`"
        :class="[
            'min-w-0 scroll-mt-8',
            preset.sections === 'ruled' && 'border-t border-border pt-10',
        ]"
    >
        <PublicHero
            v-if="section.type === 'hero'"
            :section="section"
            :variant="preset.hero"
            :primary="primary"
            :heading-id="`${section.id}-title`"
        />
        <div
            v-else
            :class="[
                'grid min-w-0 gap-6 sm:gap-8',
                preset.sections === 'columns' &&
                    section.type === 'introduction' &&
                    'lg:grid-cols-[1fr_2fr]',
            ]"
        >
            <PublicHeading
                :title="section.title"
                :heading-id="`${section.id}-title`"
            />
            <PublicRichText
                v-if="section.type === 'introduction'"
                :html="section.body"
            />
            <ul
                v-else-if="
                    section.type === 'services' || section.type === 'strengths'
                "
                :class="[
                    'grid gap-x-10 gap-y-8',
                    preset.sections === 'columns' &&
                        'sm:grid-cols-2 lg:grid-cols-3',
                    preset.sections === 'ruled' && 'sm:grid-cols-2',
                    preset.sections === 'compact' &&
                        'divide-y divide-border [&>li+li]:pt-6',
                ]"
            >
                <li
                    v-for="(item, index) in section.items"
                    :key="index"
                    :class="[
                        'min-w-0 space-y-3',
                        preset.sections === 'compact' &&
                            'sm:grid sm:grid-cols-[1fr_2fr] sm:gap-8 sm:space-y-0',
                    ]"
                >
                    <h3 class="text-lg font-semibold wrap-anywhere">
                        {{ item.title }}
                    </h3>
                    <p
                        v-if="item.description"
                        class="max-w-[70ch] leading-7 wrap-anywhere text-muted-foreground"
                    >
                        {{ item.description }}
                    </p>
                </li>
            </ul>
            <PublicArticleList
                v-else-if="section.type === 'articles'"
                :articles="articles"
                :variant="preset.articles"
            />
            <div
                v-else-if="section.type === 'contact'"
                class="flex flex-col items-start gap-6"
            >
                <p
                    v-if="section.description"
                    class="max-w-[65ch] text-lg leading-relaxed wrap-anywhere text-muted-foreground"
                >
                    {{ section.description }}
                </p>
                <PublicCta
                    :href="section.cta.href"
                    :label="section.cta.label"
                />
            </div>
        </div>
    </section>
</template>

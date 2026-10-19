<script setup lang="ts">
import type { PublicSection } from '@/types';

import type { PublicPresetDefinition } from './presets';
import PublicCta from './PublicCta.vue';

defineProps<{
    section: Extract<PublicSection, { type: 'hero' }>;
    variant: PublicPresetDefinition['hero'];
    primary?: boolean;
    headingId?: string;
}>();
</script>

<template>
    <div
        :class="[
            'grid min-w-0 gap-8 lg:gap-12',
            variant === 'split' &&
                section.image &&
                'items-center lg:grid-cols-2',
        ]"
    >
        <div class="flex min-w-0 flex-col items-start gap-6">
            <component
                :is="primary ? 'h1' : 'h2'"
                :id="headingId"
                :class="[
                    'max-w-4xl leading-tight font-semibold tracking-tight text-balance wrap-anywhere',
                    variant === 'editorial'
                        ? 'font-serif text-5xl sm:text-6xl lg:text-7xl'
                        : variant === 'simple'
                          ? 'max-w-2xl text-4xl sm:text-5xl'
                          : 'text-4xl sm:text-5xl lg:text-6xl',
                ]"
            >
                {{ section.title }}
            </component>
            <p
                v-if="section.description"
                class="max-w-[65ch] text-lg leading-relaxed wrap-anywhere text-muted-foreground"
            >
                {{ section.description }}
            </p>
            <PublicCta
                v-if="section.cta"
                :href="section.cta.href"
                :label="section.cta.label"
            />
        </div>
        <img
            v-if="section.image"
            :src="section.image.url"
            :alt="section.image.alt"
            width="1200"
            height="800"
            :loading="primary ? 'eager' : 'lazy'"
            :class="[
                'w-full bg-muted object-cover',
                variant === 'simple'
                    ? 'aspect-[2/1] rounded-lg'
                    : 'aspect-[3/2] rounded-xl',
            ]"
        />
    </div>
</template>

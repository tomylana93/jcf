<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppFooter from '@/components/layout/AppFooter.vue';
import { publicPresets } from '@/components/public/presets';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicLink from '@/components/public/PublicLink.vue';
import type { PublicWebsite } from '@/types';

const page = usePage<{ website?: PublicWebsite }>();
const footer = computed(() => page.props.website?.footer);
const preset = computed(
    () => publicPresets[page.props.website?.preset ?? 'corporate'],
);
</script>

<template>
    <footer
        v-if="footer && (footer.text || footer.links.length)"
        class="mt-auto border-t border-border py-8"
    >
        <PublicContainer
            :width="preset.width"
            class="flex flex-col gap-6 sm:flex-row sm:justify-between"
        >
            <p
                v-if="footer.text"
                class="max-w-[65ch] text-sm leading-6 wrap-anywhere text-muted-foreground"
            >
                {{ footer.text }}
            </p>
            <ul
                v-if="footer.links.length"
                class="flex flex-wrap gap-x-6 gap-y-3"
            >
                <li
                    v-for="link in footer.links"
                    :key="link.href"
                >
                    <PublicLink
                        :href="link.href"
                        class="inline-flex min-h-11 items-center text-sm wrap-anywhere underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-ring"
                    >
                        {{ link.label }}
                    </PublicLink>
                </li>
            </ul>
        </PublicContainer>
    </footer>
    <AppFooter
        v-else
        class="border-t border-border py-6"
    />
</template>

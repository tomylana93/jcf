<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import PublicFooter from '@/components/layout/PublicFooter.vue';
import PublicNavigation from '@/components/layout/PublicNavigation.vue';
import { publicPresets } from '@/components/public/presets';
import PublicContainer from '@/components/public/PublicContainer.vue';
import { useBrowserTimezone } from '@/composables/useBrowserTimezone';
import { useTranslations } from '@/composables/useTranslations';
import type { PublicWebsite } from '@/types';

const page = usePage<{ website?: PublicWebsite }>();
const preset = computed(
    () => publicPresets[page.props.website?.preset ?? 'corporate'],
);

const { t } = useTranslations();

useBrowserTimezone();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <a
            href="#public-content"
            class="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:absolute focus:top-4 focus:left-4"
        >
            {{ t('navigation.label.skip_content') }}
        </a>
        <PublicNavigation />
        <main
            id="public-content"
            tabindex="-1"
            class="flex flex-1 flex-col py-12 sm:py-20"
        >
            <PublicContainer :width="preset.width"><slot /></PublicContainer>
        </main>
        <PublicFooter />
    </div>
</template>

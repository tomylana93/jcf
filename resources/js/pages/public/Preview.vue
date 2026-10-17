<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import PublicPageContent from '@/components/public/PublicPageContent.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { PublicWebsite } from '@/types';

const props = defineProps<{
    website: PublicWebsite;
}>();

const { t } = useTranslations();

const mode = computed<'draft' | 'published' | 'empty'>(
    () => props.website.preview?.mode ?? 'published',
);

const banner = computed(() =>
    mode.value === 'draft'
        ? t('website.status.preview_draft')
        : mode.value === 'empty'
          ? t('website.status.preview_empty')
          : t('website.status.preview_published'),
);

const bannerTone = computed(() =>
    mode.value === 'draft'
        ? 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-300 dark:bg-amber-950 dark:text-amber-200'
        : mode.value === 'empty'
          ? 'border-border bg-muted text-muted-foreground'
          : 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-300 dark:bg-blue-950 dark:text-blue-200',
);
</script>

<template>
    <section class="flex flex-col gap-8">
        <Head :title="`Preview: ${props.website.page.title}`">
            <meta
                head-key="robots"
                name="robots"
                content="noindex, nofollow"
            />
        </Head>
        <div
            role="status"
            aria-live="polite"
            class="rounded-lg border px-4 py-3 text-sm"
            :class="bannerTone"
            data-test="website_preview_banner"
        >
            {{ banner }}
        </div>
        <PublicPageContent :website="props.website" />
    </section>
</template>

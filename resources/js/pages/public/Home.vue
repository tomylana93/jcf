<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import PublicPageContent from '@/components/public/PublicPageContent.vue';
import PublicSeo from '@/components/public/PublicSeo.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { PublicHomeProps } from '@/types';

defineProps<PublicHomeProps>();

const page = usePage();
const { t } = useTranslations();
</script>

<template>
    <section class="my-auto flex flex-col items-start gap-8">
        <PublicSeo
            v-if="website"
            :seo="website.page.seo"
        />
        <PublicPageContent
            v-if="website"
            :website="website"
        />
        <div
            v-else
            id="public-introduction"
            class="flex max-w-3xl flex-col gap-6"
        >
            <h1
                class="text-4xl leading-tight font-semibold tracking-tight wrap-anywhere sm:text-5xl lg:text-6xl"
            >
                {{ page.props.general.site_name }}
            </h1>
            <p
                v-if="page.props.general.site_description"
                class="text-lg leading-relaxed wrap-anywhere text-muted-foreground sm:text-xl"
            >
                {{ page.props.general.site_description }}
            </p>
        </div>
        <Button
            v-if="!website"
            as="a"
            href="#public-introduction"
            size="lg"
        >
            {{ t('navigation.link.explore') }}
        </Button>
    </section>
</template>

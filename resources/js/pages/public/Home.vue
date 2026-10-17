<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

import PublicPageContent from '@/components/public/PublicPageContent.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { PublicHomeProps } from '@/types';

defineProps<PublicHomeProps>();

const page = usePage();
const { t } = useTranslations();
</script>

<template>
    <section class="my-auto flex flex-col items-start gap-8">
        <Head title="">
            <meta
                head-key="description"
                name="description"
                :content="page.props.general.site_description"
            />
            <link
                head-key="canonical"
                rel="canonical"
                :href="canonicalUrl"
            />
            <meta
                head-key="og:type"
                property="og:type"
                content="website"
            />
            <meta
                head-key="og:title"
                property="og:title"
                :content="page.props.general.site_name"
            />
            <meta
                head-key="og:description"
                property="og:description"
                :content="page.props.general.site_description"
            />
            <meta
                head-key="og:url"
                property="og:url"
                :content="canonicalUrl"
            />
            <meta
                head-key="og:site_name"
                property="og:site_name"
                :content="page.props.general.site_name"
            />
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content="summary"
            />
            <meta
                head-key="twitter:title"
                name="twitter:title"
                :content="page.props.general.site_name"
            />
            <meta
                head-key="twitter:description"
                name="twitter:description"
                :content="page.props.general.site_description"
            />
        </Head>
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

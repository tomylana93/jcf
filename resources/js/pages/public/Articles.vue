<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PublicPageContent from '@/components/public/PublicPageContent.vue';
import type { PublicWebsite } from '@/types';
defineProps<{ website: PublicWebsite }>();
</script>
<template>
    <section class="flex flex-col gap-8">
        <Head :title="website.page.seo.title">
            <meta
                head-key="description"
                name="description"
                :content="website.page.seo.description"
            />
            <meta
                head-key="robots"
                name="robots"
                :content="website.page.seo.robots"
            />
            <link
                head-key="canonical"
                rel="canonical"
                :href="website.page.seo.canonicalUrl"
            />
            <meta
                head-key="og:title"
                property="og:title"
                :content="website.page.seo.title"
            />
            <meta
                head-key="og:description"
                property="og:description"
                :content="website.page.seo.description"
            />
            <meta
                head-key="og:type"
                property="og:type"
                :content="
                    website.page.kind === 'article' ? 'article' : 'website'
                "
            />
            <meta
                head-key="og:url"
                property="og:url"
                :content="website.page.seo.canonicalUrl"
            />
        </Head>
        <PublicPageContent :website="website" />
    </section>
</template>

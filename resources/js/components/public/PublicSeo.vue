<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import type { PublicSeo } from '@/types';

defineProps<{ seo: PublicSeo }>();
</script>

<template>
    <Head :title="seo.title">
        <meta
            head-key="description"
            name="description"
            :content="seo.description"
        />
        <meta
            head-key="robots"
            name="robots"
            :content="seo.robots"
        />
        <link
            head-key="canonical"
            rel="canonical"
            :href="seo.canonicalUrl"
        />
        <meta
            head-key="og:type"
            property="og:type"
            :content="seo.type ?? 'website'"
        />
        <meta
            head-key="og:title"
            property="og:title"
            :content="seo.title"
        />
        <meta
            head-key="og:description"
            property="og:description"
            :content="seo.description"
        />
        <meta
            head-key="og:url"
            property="og:url"
            :content="seo.canonicalUrl"
        />
        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="seo.siteName ?? ''"
        />
        <meta
            head-key="og:locale"
            property="og:locale"
            :content="seo.locale.replaceAll('-', '_')"
        />
        <meta
            head-key="twitter:card"
            name="twitter:card"
            :content="seo.image ? 'summary_large_image' : 'summary'"
        />
        <meta
            head-key="twitter:title"
            name="twitter:title"
            :content="seo.title"
        />
        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="seo.description"
        />
        <meta
            v-if="seo.image"
            head-key="og:image"
            property="og:image"
            :content="seo.image.url"
        />
        <meta
            v-if="seo.image"
            head-key="og:image:alt"
            property="og:image:alt"
            :content="seo.image.alt"
        />
        <meta
            v-if="seo.image"
            head-key="twitter:image"
            name="twitter:image"
            :content="seo.image.url"
        />
        <meta
            v-if="seo.image"
            head-key="twitter:image:alt"
            name="twitter:image:alt"
            :content="seo.image.alt"
        />
        <component
            :is="'script'"
            v-if="seo.structuredData"
            head-key="public-schema"
            type="application/ld+json"
        >
            {{ seo.structuredData }}
        </component>
    </Head>
</template>

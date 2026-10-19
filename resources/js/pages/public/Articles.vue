<script setup lang="ts">
import PublicPageContent from '@/components/public/PublicPageContent.vue';
import PublicSeo from '@/components/public/PublicSeo.vue';
import type { PublicWebsite } from '@/types';
defineProps<{ website: PublicWebsite }>();
</script>
<template>
    <section class="flex flex-col gap-8">
        <PublicSeo :seo="website.page.seo" />
        <PublicPageContent :website="website" />
    </section>
</template>

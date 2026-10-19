<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import PublicContactForm from '@/components/public/PublicContactForm.vue';
import PublicContactInfo from '@/components/public/PublicContactInfo.vue';
import PublicPageContent from '@/components/public/PublicPageContent.vue';
import type { PublicWebsite } from '@/types';

const props = defineProps<{ website: PublicWebsite }>();
const hasContact = computed(() => {
    const contact = props.website.contact;
    return (
        !!contact &&
        !!(
            contact.email ||
            contact.phone ||
            contact.address ||
            contact.hours ||
            contact.location_url ||
            contact.social_links.length
        )
    );
});
</script>
<template>
    <section class="flex min-w-0 flex-col gap-12">
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
        </Head>
        <PublicPageContent
            :website="website"
            hide-contact-info
        />
        <div
            :class="[
                'grid min-w-0 gap-12 lg:gap-20',
                hasContact && website.preset !== 'minimal' && 'lg:grid-cols-2',
            ]"
        >
            <PublicContactInfo
                v-if="hasContact && website.contact"
                :contact="website.contact"
            />
            <PublicContactForm class="w-full max-w-2xl" />
        </div>
    </section>
</template>

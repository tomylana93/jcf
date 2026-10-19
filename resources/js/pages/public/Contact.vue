<script setup lang="ts">
import { computed } from 'vue';

import PublicContactForm from '@/components/public/PublicContactForm.vue';
import PublicContactInfo from '@/components/public/PublicContactInfo.vue';
import PublicPageContent from '@/components/public/PublicPageContent.vue';
import PublicSeo from '@/components/public/PublicSeo.vue';
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
        <PublicSeo :seo="website.page.seo" />
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

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { publicPresets } from '@/components/public/presets';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicLink from '@/components/public/PublicLink.vue';
import AppLogo from '@/components/shared/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, home } from '@/routes';
import type { PublicContext, PublicWebsite } from '@/types';

const page = usePage<{
    publicContext: PublicContext;
    website?: PublicWebsite;
}>();

const preset = computed(
    () => publicPresets[page.props.website?.preset ?? 'corporate'],
);
const items = computed(
    () =>
        page.props.website?.navigation ?? [
            {
                label: t('navigation.link.home'),
                href: home.url(),
                active: true,
            },
        ],
);

const { t } = useTranslations();
</script>

<template>
    <header class="border-b border-border">
        <PublicContainer
            :width="preset.width"
            class="flex flex-wrap items-center justify-between gap-4 py-4"
        >
            <Link
                :href="home()"
                :aria-label="page.props.general.site_name"
                class="max-w-full min-w-0 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
                <AppLogo />
            </Link>
            <nav
                :aria-label="t('navigation.label.main')"
                class="flex max-w-full min-w-0 flex-wrap items-center gap-x-5 gap-y-2"
            >
                <PublicLink
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    :aria-current="item.active ? 'page' : undefined"
                    class="inline-flex min-h-11 max-w-full items-center rounded-sm text-sm font-medium wrap-anywhere underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                >
                    {{ item.label }}
                </PublicLink>
                <Button
                    v-if="page.props.publicContext.canAccessDashboard"
                    :as="Link"
                    :href="dashboard()"
                    size="sm"
                >
                    {{ t('navigation.link.dashboard') }}
                </Button>
            </nav>
        </PublicContainer>
    </header>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import AppLogo from '@/components/shared/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, home, login } from '@/routes';

const page = usePage();

const { t } = useTranslations();

const siteName = page.props.general.site_name;
</script>

<template>
    <header class="border-b border-border">
        <div
            class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8"
        >
            <Link
                :href="home()"
                :aria-label="siteName"
                class="max-w-full min-w-0 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
                <AppLogo />
            </Link>
            <nav
                :aria-label="t('navigation.label.main')"
                class="flex shrink-0 items-center gap-4"
            >
                <Link
                    :href="home()"
                    aria-current="page"
                    class="rounded-sm text-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                >
                    {{ t('navigation.link.home') }}
                </Link>
                <Button
                    :as="Link"
                    :href="page.props.auth.user ? dashboard() : login()"
                    size="sm"
                >
                    {{
                        page.props.auth.user
                            ? t('navigation.link.dashboard')
                            : t('auth.button.login')
                    }}
                </Button>
            </nav>
        </div>
    </header>
</template>

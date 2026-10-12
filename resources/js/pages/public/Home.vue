<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, login } from '@/routes';

const { t } = useTranslations();
</script>

<template>
    <section class="my-auto flex flex-col items-start gap-8">
        <Head :title="t('navigation.link.home')" />
        <div class="flex max-w-3xl flex-col gap-6">
            <h1
                class="text-4xl leading-tight font-semibold tracking-tight wrap-anywhere sm:text-5xl lg:text-6xl"
            >
                {{ t('navigation.link.home') }}
            </h1>
            <p
                class="text-lg leading-relaxed wrap-anywhere text-muted-foreground sm:text-xl"
            >
                {{ t('navigation.label.main') }}
            </p>
        </div>
        <Button
            :as="Link"
            :href="$page.props.auth.user ? dashboard() : login()"
            size="lg"
        >
            {{
                $page.props.auth.user
                    ? t('navigation.link.dashboard')
                    : t('auth.button.login')
            }}
        </Button>
    </section>
</template>

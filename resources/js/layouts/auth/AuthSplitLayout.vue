<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AuthFooter from '@/components/layout/AuthFooter.vue';
import AppLogo from '@/components/shared/AppLogo.vue';
import { home } from '@/routes';

const page = usePage();

const customBackground = computed(() => page.props.brand.auth_background_url);

const backgroundFailed = ref(false);

const backgroundSource = computed(() =>
    !backgroundFailed.value && customBackground.value
        ? customBackground.value
        : '/images/auth-background.webp',
);

watch(customBackground, () => {
    backgroundFailed.value = false;
});

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid min-h-svh px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col overflow-hidden bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <img
                :src="backgroundSource"
                alt=""
                class="absolute inset-0 size-full object-cover"
                @error="backgroundFailed = true"
            />
            <div
                class="absolute inset-0 bg-linear-to-b from-black/60 via-black/10 to-black/70"
            />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <AppLogo class="dark" />
            </Link>
            <blockquote
                v-if="page.props.general.site_description?.trim()"
                class="relative z-20 mt-auto pt-10 text-lg leading-relaxed wrap-break-word whitespace-pre-line italic"
                data-test="app-description"
            >
                “{{ page.props.general.site_description }}”
            </blockquote>
        </div>
        <div class="flex min-h-svh flex-col">
            <main class="flex flex-1 items-center justify-center py-12 lg:p-8">
                <div
                    class="mx-auto flex w-full max-w-sm flex-col justify-center gap-6"
                >
                    <Link
                        v-if="page.props.general.public_pages_enabled"
                        :href="home()"
                        class="flex items-center justify-center gap-2 rounded-md text-sm font-medium wrap-anywhere lg:hidden"
                    >
                        <AppLogo />
                    </Link>
                    <div class="flex flex-col space-y-2 text-center">
                        <h1
                            class="text-xl font-medium tracking-tight"
                            v-if="title"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="text-sm text-muted-foreground"
                            v-if="description"
                        >
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </main>
            <AuthFooter />
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AuthFooter from '@/components/layout/AuthFooter.vue';
import AppLogo from '@/components/shared/AppLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <main class="flex flex-1 items-center justify-center p-6 md:p-10">
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <Link
                            :href="home()"
                            class="flex flex-col items-center gap-2 font-medium"
                        >
                            <AppLogo />
                            <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-medium">{{ title }}</h1>
                            <p
                                class="text-center text-sm text-muted-foreground"
                            >
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </main>
        <AuthFooter />
    </div>
</template>

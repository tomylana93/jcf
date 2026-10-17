<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { PublicContext } from '@/types';

const page = usePage<{ publicContext: PublicContext }>();

const credit = { owner: 'tomylana93', since: 2026 };

const currentYear = computed(() => page.props.publicContext.year);

const years = computed(() =>
    currentYear.value > credit.since
        ? `${credit.since}–${currentYear.value}`
        : `${credit.since}`,
);
</script>

<template>
    <footer
        class="mt-auto shrink-0 px-4 py-4 text-center text-xs text-muted-foreground"
    >
        © {{ years }}
        <a
            :href="`https://github.com/${credit.owner}`"
            class="rounded-sm underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            target="_blank"
            rel="noopener noreferrer"
        >
            {{ credit.owner }}
        </a>
    </footer>
</template>

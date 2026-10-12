<script setup lang="ts">
import type { TranslationLocale } from '@/types';

defineProps<{
    locale: TranslationLocale;
}>();
</script>

<template>
    <!-- Inline SVG flags render on every browser/OS. Flag emoji rely on the
        system emoji font, which Windows (Segoe UI Emoji) does not provide,
        so Chrome on Windows falls back to plain "US"/"ID" text. -->
    <svg
        v-if="locale === 'en'"
        viewBox="0 0 24 16"
        aria-hidden="true"
        focusable="false"
        class="block h-4 w-6 overflow-hidden rounded-[2px] ring-1 ring-black/10"
    >
        <rect width="24" height="16" fill="#b31942" />
        <rect y="1.23" width="24" height="1.23" fill="#fff" />
        <rect y="3.69" width="24" height="1.23" fill="#fff" />
        <rect y="6.15" width="24" height="1.23" fill="#fff" />
        <rect y="8.62" width="24" height="1.23" fill="#fff" />
        <rect y="11.08" width="24" height="1.23" fill="#fff" />
        <rect y="13.54" width="24" height="1.23" fill="#fff" />
        <rect width="9.6" height="8.62" fill="#0a3161" />
        <g fill="#fff">
            <circle cx="1.2" cy="1.3" r="0.5" />
            <circle cx="3" cy="1.3" r="0.5" />
            <circle cx="4.8" cy="1.3" r="0.5" />
            <circle cx="6.6" cy="1.3" r="0.5" />
            <circle cx="8.4" cy="1.3" r="0.5" />
            <circle cx="1.2" cy="3.2" r="0.5" />
            <circle cx="3" cy="3.2" r="0.5" />
            <circle cx="4.8" cy="3.2" r="0.5" />
            <circle cx="6.6" cy="3.2" r="0.5" />
            <circle cx="8.4" cy="3.2" r="0.5" />
            <circle cx="1.2" cy="5.1" r="0.5" />
            <circle cx="3" cy="5.1" r="0.5" />
            <circle cx="4.8" cy="5.1" r="0.5" />
            <circle cx="6.6" cy="5.1" r="0.5" />
            <circle cx="8.4" cy="5.1" r="0.5" />
            <circle cx="1.2" cy="7" r="0.5" />
            <circle cx="3" cy="7" r="0.5" />
            <circle cx="4.8" cy="7" r="0.5" />
            <circle cx="6.6" cy="7" r="0.5" />
            <circle cx="8.4" cy="7" r="0.5" />
        </g>
    </svg>
    <svg
        v-else
        viewBox="0 0 24 16"
        aria-hidden="true"
        focusable="false"
        class="block h-4 w-6 overflow-hidden rounded-[2px] ring-1 ring-black/10"
    >
        <rect width="24" height="8" fill="#e70011" />
        <rect y="8" width="24" height="8" fill="#fff" />
    </svg>
</template>

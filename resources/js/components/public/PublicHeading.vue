<script setup lang="ts">
defineProps<{ title: string; headingId?: string }>();
</script>

<template>
    <h2
        :id="headingId"
        class="max-w-3xl text-2xl leading-tight font-semibold tracking-tight text-balance wrap-anywhere sm:text-3xl"
    >
        {{ title }}
    </h2>
</template>

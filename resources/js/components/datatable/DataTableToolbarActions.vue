<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import type { DataTableAction } from '@/types';

defineProps<{ actions: DataTableAction[] }>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <template v-for="action in actions" :key="action.label">
            <Button
                v-if="action.href && !action.disabled"
                :variant="action.variant"
                class="h-9"
                as-child
            >
                <Link :href="action.href">
                    <component
                        :is="action.icon"
                        v-if="action.icon"
                        data-icon="inline-start"
                    />
                    {{ action.label }}
                </Link>
            </Button>
            <Button
                v-else
                :variant="action.variant"
                class="h-9"
                :disabled="action.disabled"
                @click="action.onClick?.()"
            >
                <component
                    :is="action.icon"
                    v-if="action.icon"
                    data-icon="inline-start"
                />
                {{ action.label }}
            </Button>
        </template>
    </div>
</template>

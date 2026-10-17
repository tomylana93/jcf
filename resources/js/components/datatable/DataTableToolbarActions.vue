<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import type { DataTableAction } from '@/types';

defineProps<{ actions: DataTableAction[] }>();
</script>

<template>
    <div
        data-slot="datatable-toolbar-actions"
        class="flex w-full flex-col gap-2 @sm/toolbar:w-auto @sm/toolbar:flex-row @sm/toolbar:flex-wrap @sm/toolbar:items-center"
    >
        <template
            v-for="action in actions"
            :key="action.label"
        >
            <Button
                v-if="action.href && !action.disabled"
                :variant="action.variant"
                size="default"
                class="h-9 w-full @sm/toolbar:w-auto"
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
                size="default"
                class="h-9 w-full @sm/toolbar:w-auto"
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

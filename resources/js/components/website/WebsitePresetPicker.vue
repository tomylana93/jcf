<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import { publicPresets } from '@/components/public/presets';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { preview } from '@/routes/website';
import type { WebsitePresetKey, WebsitePresetOption } from '@/types';

const props = defineProps<{
    preset: WebsitePresetKey;
    options: WebsitePresetOption[];
    disabled?: boolean;
    /** Applying switches the whole public site; keep it behind an explicit confirmation. */
    confirmRequired?: boolean;
}>();

const emit = defineEmits<{
    apply: [preset: WebsitePresetKey];
}>();

const { t } = useTranslations();

const selection = ref<WebsitePresetKey>(props.preset);

const confirming = ref(false);

watch(
    () => props.preset,
    (value) => {
        selection.value = value;
    },
);

const changed = computed(() => selection.value !== props.preset);

function chips(option: WebsitePresetOption): string[] {
    return publicPresets[option.value].order.map((type) =>
        t(`website.label.section.type.${type}`),
    );
}

function apply(): void {
    if (props.confirmRequired) {
        confirming.value = true;

        return;
    }
    emit('apply', selection.value);
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ t('website.card.preset.title') }}</CardTitle>
            <CardDescription>
                {{ t('website.card.preset.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-6">
            <div
                role="radiogroup"
                :aria-label="t('website.card.preset.title')"
                class="grid gap-3 md:grid-cols-3"
            >
                <button
                    v-for="option in props.options"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="selection === option.value"
                    :disabled="props.disabled"
                    class="flex flex-col gap-2 rounded-xl border p-4 text-left transition-colors hover:bg-accent disabled:opacity-60"
                    :class="
                        selection === option.value &&
                        'border-primary ring-2 ring-ring'
                    "
                    :data-test="`website_preset_${option.value}`"
                    @click="selection = option.value"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold">
                            {{ option.label }}
                        </span>
                        <span
                            v-if="props.preset === option.value"
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Check class="size-3.5" />
                            {{ t('website.card.preset.current') }}
                        </span>
                    </div>
                    <span class="text-sm text-muted-foreground">
                        {{ t(`website.card.preset.${option.value}`) }}
                    </span>
                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="chip in chips(option)"
                            :key="chip"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ chip }}
                        </Badge>
                    </div>
                    <a
                        v-if="selection === option.value"
                        :href="
                            preview(
                                { page: 'home' },
                                { query: { preset: option.value } },
                            ).url
                        "
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 text-sm text-primary underline-offset-4 hover:underline"
                        :data-test="`website_preset_preview_${option.value}`"
                    >
                        {{ t('website.button.preview') }}
                    </a>
                </button>
            </div>
            <div class="flex items-center gap-3">
                <Button
                    type="button"
                    size="sm"
                    :disabled="props.disabled || !changed"
                    data-test="apply_website_preset"
                    @click="apply"
                >
                    {{ t('website.button.apply_preset') }}
                </Button>
                <p
                    v-if="!changed"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('website.helper.preset') }}
                </p>
            </div>
        </CardContent>
    </Card>

    <AlertDialog v-model:open="confirming">
        <AlertDialogContent class="max-w-md">
            <AlertDialogHeader>
                <AlertDialogTitle>
                    {{ t('website.button.apply_preset') }}
                </AlertDialogTitle>
                <AlertDialogDescription>
                    {{ t('website.card.preset.description') }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>
                    {{ t('common.button.cancel') }}
                </AlertDialogCancel>
                <AlertDialogAction
                    data-test="confirm_apply_website_preset"
                    @click="emit('apply', selection)"
                >
                    {{ t('website.button.apply_preset') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

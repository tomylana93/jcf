<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    modelValue: string;
    normalized: string | null;
    disabled?: boolean;
}>();

defineEmits<{
    'update:modelValue': [value: string];
    apply: [];
    confirm: [];
    cancel: [];
}>();

const { t } = useTranslations();
</script>
<template>
    <div class="flex flex-col gap-4 p-4">
        <p class="text-sm text-muted-foreground">
            {{ t('editor.helper.html') }}
        </p>
        <Textarea
            :model-value="modelValue"
            :disabled="disabled"
            :aria-label="t('editor.label.html')"
            class="min-h-80 font-mono"
            spellcheck="false"
            @update:model-value="$emit('update:modelValue', String($event))"
        />
        <Alert v-if="normalized !== null">
            <AlertTitle>{{ t('editor.label.normalized') }}</AlertTitle>
            <AlertDescription class="flex flex-col gap-3">
                <p>{{ t('editor.helper.normalized') }}</p>
                <pre
                    class="max-h-60 overflow-auto font-mono text-xs whitespace-pre-wrap"
                    >{{ normalized }}</pre>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="disabled"
                    @click="$emit('confirm')"
                >
                    {{ t('editor.button.confirm_html') }}
                </Button>
            </AlertDescription>
        </Alert>
        <div class="flex gap-2">
            <Button
                type="button"
                :disabled="disabled"
                @click="$emit('apply')"
            >
                {{ t('editor.button.apply') }}
            </Button>
            <Button
                type="button"
                variant="outline"
                :disabled="disabled"
                @click="$emit('cancel')"
            >
                {{ t('common.button.cancel') }}
            </Button>
        </div>
    </div>
</template>

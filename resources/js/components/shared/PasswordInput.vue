<script setup lang="ts">
import { Eye, EyeOff, LockKeyhole } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';

import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    class?: HTMLAttributes['class'];
    autofocus?: boolean;
}>();

const showPassword = ref(false);

const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <InputGroup :class="props.class">
        <InputGroupInput
            v-focus="props.autofocus"
            ref="inputRef"
            v-bind="$attrs"
            :type="showPassword ? 'text' : 'password'"
        />

        <InputGroupAddon>
            <LockKeyhole aria-hidden="true" />
        </InputGroupAddon>

        <InputGroupAddon align="inline-end">
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <InputGroupButton
                            type="button"
                            size="icon-xs"
                            :disabled="$attrs.disabled"
                            :aria-label="
                                showPassword
                                    ? t('common.button.hide_password')
                                    : t('common.button.show_password')
                            "
                            @click="showPassword = !showPassword"
                        >
                            <EyeOff
                                v-if="showPassword"
                                aria-hidden="true"
                            />
                            <Eye
                                v-else
                                aria-hidden="true"
                            />
                        </InputGroupButton>
                    </TooltipTrigger>

                    <TooltipContent>
                        {{
                            showPassword
                                ? t('common.button.hide_password')
                                : t('common.button.show_password')
                        }}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </InputGroupAddon>
    </InputGroup>
</template>

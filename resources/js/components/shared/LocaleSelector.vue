<script setup lang="ts">
import { computed, useTemplateRef } from 'vue';
import LocaleFlag from '@/components/shared/LocaleFlag.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const trigger = useTemplateRef<InstanceType<typeof Button>>('trigger');

const { locale, options, updateLocale } = useLocale();

const tooltip = computed(() => t('locale.label.change'));

function selectLocale(value: unknown): void {
    const option = options.value.find((item) => item.value === value);
    if (option) {
        updateLocale(option.value);
    }
}
</script>

<template>
    <TooltipProvider>
        <DropdownMenu>
            <Tooltip>
                <TooltipTrigger as-child>
                    <DropdownMenuTrigger as-child>
                        <Button
                            ref="trigger"
                            variant="ghost"
                            size="icon"
                            :aria-label="tooltip"
                        >
                            <LocaleFlag :locale="locale" />
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent side="bottom">
                    {{ tooltip }}
                </TooltipContent>
            </Tooltip>
            <DropdownMenuContent
                :reference="trigger?.$el"
                align="end"
                class="min-w-0"
            >
                <DropdownMenuRadioGroup
                    :model-value="locale"
                    @update:model-value="selectLocale"
                >
                    <DropdownMenuRadioItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        :aria-label="option.label"
                        :title="option.label"
                    >
                        <LocaleFlag :locale="option.value" />
                    </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    </TooltipProvider>
</template>

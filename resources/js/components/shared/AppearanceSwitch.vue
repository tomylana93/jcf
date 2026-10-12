<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useTemplateRef } from 'vue';
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
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const trigger = useTemplateRef<InstanceType<typeof Button>>('trigger');

const { appearance, updateAppearance } = useAppearance();

function selectAppearance(value: unknown) {
    if (value === 'light' || value === 'dark' || value === 'system') {
        updateAppearance(value);
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
                            :aria-label="
                                t('appearance.helper.change', {
                                    theme: t(`appearance.label.${appearance}`),
                                })
                            "
                        >
                            <Sun v-if="appearance === 'light'" />
                            <Moon v-else-if="appearance === 'dark'" />
                            <Monitor v-else />
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent side="bottom">
                    {{
                        t('appearance.helper.current', {
                            theme: t(`appearance.label.${appearance}`),
                        })
                    }}
                </TooltipContent>
            </Tooltip>
            <DropdownMenuContent :reference="trigger?.$el" align="end">
                <DropdownMenuRadioGroup
                    :model-value="appearance"
                    @update:model-value="selectAppearance"
                >
                    <DropdownMenuRadioItem value="light">
                        <Sun /> {{ t('appearance.label.light') }}
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="dark">
                        <Moon /> {{ t('appearance.label.dark') }}
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="system">
                        <Monitor /> {{ t('appearance.label.system') }}
                    </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    </TooltipProvider>
</template>

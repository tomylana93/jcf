<script setup lang="ts">
import { Settings2 } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const { locale, options, updateLocale } = useLocale();
const { appearance, updateAppearance } = useAppearance();
const themes = ['light', 'dark', 'system'] as const;

function selectLocale(value: unknown): void {
    const option = options.value.find((item) => item.value === value);
    if (option) {
        updateLocale(option.value);
    }
}

function selectAppearance(value: unknown): void {
    if (value === 'light' || value === 'dark' || value === 'system') {
        updateAppearance(value);
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                data-test="header-preferences"
                :aria-label="t('navigation.label.preferences')"
            >
                <Settings2 />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>
                {{ t('locale.label.change') }}
            </DropdownMenuLabel>
            <DropdownMenuRadioGroup
                :model-value="locale"
                @update:model-value="selectLocale"
            >
                <DropdownMenuRadioItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                    :data-test="`header-locale-${option.value}`"
                >
                    {{ option.label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
            <DropdownMenuSeparator />
            <DropdownMenuLabel>
                {{
                    t('appearance.helper.current', {
                        theme: t(`appearance.label.${appearance}`),
                    })
                }}
            </DropdownMenuLabel>
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="selectAppearance"
            >
                <DropdownMenuRadioItem
                    v-for="theme in themes"
                    :key="theme"
                    :value="theme"
                    :data-test="`header-theme-${theme}`"
                >
                    {{ t(`appearance.label.${theme}`) }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

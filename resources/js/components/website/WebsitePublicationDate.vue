<script setup lang="ts">
import {
    type DateValue,
    getLocalTimeZone,
    parseDate,
    today,
} from '@internationalized/date';
import { CalendarDays } from '@lucide/vue';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useTranslations } from '@/composables/useTranslations';

const model = defineModel<string>({ required: true });
defineProps<{ disabled?: boolean; invalid?: boolean }>();
const { t, locale } = useTranslations();
const open = ref(false);
const selected = computed(() =>
    model.value ? parseDate(model.value.slice(0, 10)) : undefined,
);
const time = computed({
    get: () => model.value.slice(11, 16),
    set: (value: string) => {
        if (selected.value && value)
            model.value = `${selected.value.toString()}T${value}`;
    },
});
const label = computed(() =>
    selected.value
        ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'long' }).format(
              selected.value.toDate(getLocalTimeZone()),
          )
        : t('article.placeholder.date'),
);
function choose(value: DateValue | undefined): void {
    model.value = value ? `${value.toString()}T${time.value || '00:00'}` : '';
    open.value = false;
}
</script>

<template>
    <FieldGroup class="flex flex-row flex-wrap items-end gap-3">
        <Field
            class="w-full sm:w-auto"
            :data-invalid="invalid"
        >
            <FieldLabel for="article-date">
                {{ t('article.label.date') }}
            </FieldLabel>
            <Popover v-model:open="open">
                <PopoverTrigger as-child>
                    <Button
                        id="article-date"
                        type="button"
                        variant="outline"
                        :disabled="disabled"
                        :aria-invalid="invalid"
                    >
                        <CalendarDays />
                        {{ label }}
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    class="w-auto p-0"
                    align="start"
                >
                    <Calendar
                        :model-value="selected"
                        :locale="locale"
                        :max-value="today(getLocalTimeZone())"
                        initial-focus
                        @update:model-value="choose"
                    />
                </PopoverContent>
            </Popover>
        </Field>
        <Field class="w-full sm:w-36">
            <FieldLabel for="article-time">
                {{ t('article.label.time') }}
            </FieldLabel>
            <Input
                id="article-time"
                v-model="time"
                type="time"
                :disabled="disabled || !selected"
            />
        </Field>
        <Button
            v-if="model"
            type="button"
            variant="ghost"
            :disabled="disabled"
            @click="model = ''"
        >
            {{ t('article.button.clear_date') }}
        </Button>
    </FieldGroup>
</template>

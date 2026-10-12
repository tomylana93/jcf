<script setup lang="ts">
import { Bold, Italic, Underline, Strikethrough } from '@lucide/vue';
import type { Editor } from '@tiptap/vue-3';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Toggle } from '@/components/ui/toggle';
import { useTranslations } from '@/composables/useTranslations';
import { runEditorAction } from '@/lib/editor';
import type { EditorAction, TranslationKey } from '@/types';
defineProps<{ editor: Editor }>();
const { t } = useTranslations();

const controls: {
    action: EditorAction;
    icon: typeof Bold;
    label: TranslationKey;
}[] = [
    { action: 'bold', icon: Bold, label: 'editor.button.bold' },
    { action: 'italic', icon: Italic, label: 'editor.button.italic' },
    { action: 'underline', icon: Underline, label: 'editor.button.underline' },
    { action: 'strike', icon: Strikethrough, label: 'editor.button.strike' },
];
</script>
<template>
    <div class="flex items-center gap-1">
        <Select
            :model-value="
                editor.isActive('heading')
                    ? String(editor.getAttributes('heading').level)
                    : 'paragraph'
            "
            :disabled="!editor.isEditable"
            @update:model-value="
                (value) =>
                    value === 'paragraph'
                        ? editor.chain().focus().setParagraph().run()
                        : editor
                              .chain()
                              .focus()
                              .setHeading({ level: Number(value) as 2 | 3 | 4 })
                              .run()
            "
        >
            <SelectTrigger class="w-36" :aria-label="t('editor.label.block')"
                ><SelectValue
            /></SelectTrigger>
            <SelectContent
                ><SelectGroup
                    ><SelectItem value="paragraph">{{
                        t('editor.label.paragraph')
                    }}</SelectItem
                    ><SelectItem
                        v-for="level in [2, 3, 4]"
                        :key="level"
                        :value="String(level)"
                        >{{ t('editor.label.heading', { level }) }}</SelectItem
                    ></SelectGroup
                ></SelectContent
            >
        </Select>
        <Toggle
            v-for="control in controls"
            :key="control.action"
            :data-test="`editor-action-${control.action}`"
            :model-value="editor.isActive(control.action)"
            :disabled="!editor.isEditable"
            :aria-label="t(control.label)"
            :title="t(control.label)"
            @mousedown.prevent
            @click="runEditorAction(editor, control.action)"
            ><component :is="control.icon"
        /></Toggle>
    </div>
</template>

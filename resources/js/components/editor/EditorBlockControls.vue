<script setup lang="ts">
import {
    Code,
    Eraser,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Undo2,
} from '@lucide/vue';
import type { Editor } from '@tiptap/vue-3';

import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { runEditorAction } from '@/lib/editor';
import type { EditorAction, TranslationKey } from '@/types';

defineProps<{ editor: Editor }>();

const { t } = useTranslations();

const controls: {
    action: EditorAction;
    icon: typeof List;
    label: TranslationKey;
}[] = [
    { action: 'bulletList', icon: List, label: 'editor.button.bullet_list' },
    {
        action: 'orderedList',
        icon: ListOrdered,
        label: 'editor.button.ordered_list',
    },
    { action: 'blockquote', icon: Quote, label: 'editor.button.quote' },
    { action: 'codeBlock', icon: Code, label: 'editor.button.code' },
    { action: 'horizontalRule', icon: Minus, label: 'editor.button.separator' },
    { action: 'undo', icon: Undo2, label: 'editor.button.undo' },
    { action: 'redo', icon: Redo2, label: 'editor.button.redo' },
    { action: 'clear', icon: Eraser, label: 'editor.button.clear' },
];
</script>
<template>
    <div class="flex flex-wrap items-center gap-1">
        <Button
            v-for="control in controls"
            :key="control.action"
            type="button"
            :variant="editor.isActive(control.action) ? 'secondary' : 'ghost'"
            size="icon-sm"
            :disabled="
                !editor.isEditable ||
                (control.action === 'undo' && !editor.can().undo()) ||
                (control.action === 'redo' && !editor.can().redo())
            "
            :aria-label="t(control.label)"
            :title="t(control.label)"
            @mousedown.prevent
            @click="runEditorAction(editor, control.action)"
        >
            <component :is="control.icon" />
        </Button>
    </div>
</template>

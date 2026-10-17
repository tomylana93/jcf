<script setup lang="ts">
import { EditorContent, generateHTML, generateJSON } from '@tiptap/vue-3';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useRichTextEditor } from '@/composables/useRichTextEditor';
import { useTranslations } from '@/composables/useTranslations';
import { editorExtensions, inspectEditorHtml } from '@/lib/editor';
import type { EditorDocument, EditorLinkOption } from '@/types';

import EditorContextMenu from './EditorContextMenu.vue';
import EditorHtmlSource from './EditorHtmlSource.vue';
import EditorLinkDialog from './EditorLinkDialog.vue';
import EditorToolbar from './EditorToolbar.vue';

const props = defineProps<{
    modelValue: EditorDocument;
    disabled?: boolean;
    error?: string;
    linkOptions?: EditorLinkOption[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: EditorDocument];
    'source-dirty': [value: boolean];
}>();

const { t } = useTranslations();

const editor = useRichTextEditor(
    props,
    (value) => emit('update:modelValue', value),
    t('editor.label.content'),
);

const htmlMode = ref(false);

const source = ref('');

const normalized = ref<string | null>(null);

const linkOpen = ref(false);

function enterHtml(): void {
    if (htmlMode.value) return;
    source.value = editor.value?.getHTML() ?? '';
    normalized.value = null;
    htmlMode.value = true;
}

function changeSource(value: string): void {
    source.value = value;
    normalized.value = null;
    emit('source-dirty', value !== editor.value?.getHTML());
}

function cancelHtml(): void {
    htmlMode.value = false;
    normalized.value = null;
    emit('source-dirty', false);
}

function applyHtml(confirm = false): boolean {
    if (!editor.value || props.disabled) return false;
    const json = generateJSON(source.value, editorExtensions());

    const html = generateHTML(json, editorExtensions());
    if (
        !confirm &&
        (inspectEditorHtml(source.value) || html !== source.value)
    ) {
        normalized.value = html;
        return false;
    }
    editor.value.commands.setContent(json, { errorOnInvalidContent: true });
    cancelHtml();
    return true;
}

function prepareSave(): boolean {
    return !htmlMode.value || applyHtml();
}

defineExpose({ prepareSave });
</script>
<template>
    <div
        class="overflow-hidden rounded-lg border bg-background"
        :data-invalid="!!error"
        :aria-invalid="!!error"
    >
        <div class="flex items-center justify-end gap-2 p-2">
            <Button
                type="button"
                size="sm"
                :variant="htmlMode ? 'outline' : 'secondary'"
                :disabled="disabled"
                @click="htmlMode ? applyHtml() : undefined"
            >
                {{ t('editor.label.visual') }}
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="htmlMode ? 'secondary' : 'outline'"
                :disabled="disabled"
                @click="enterHtml"
            >
                {{ t('editor.label.html') }}
            </Button>
        </div>
        <Separator />
        <EditorHtmlSource
            v-if="htmlMode"
            :model-value="source"
            :normalized="normalized"
            :disabled="disabled"
            @update:model-value="changeSource"
            @apply="applyHtml()"
            @confirm="applyHtml(true)"
            @cancel="cancelHtml"
        />
        <template v-else-if="editor">
            <EditorToolbar
                :editor="editor"
                @link="linkOpen = true"
            />
            <Separator />
            <EditorContextMenu
                :editor="editor"
                @link="linkOpen = true"
            >
                <EditorContent :editor="editor" />
            </EditorContextMenu>
            <EditorLinkDialog
                v-model:open="linkOpen"
                :editor="editor"
                :options="linkOptions"
            />
        </template>
    </div>
</template>

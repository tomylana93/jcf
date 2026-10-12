<script setup lang="ts">
import { Link } from '@lucide/vue';
import type { Editor } from '@tiptap/vue-3';

import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/composables/useTranslations';

import EditorBlockControls from './EditorBlockControls.vue';
import EditorFormattingControls from './EditorFormattingControls.vue';
import EditorTableControls from './EditorTableControls.vue';
defineProps<{ editor: Editor }>();
defineEmits<{ link: [] }>();
const { t } = useTranslations();
</script>
<template>
    <div
        role="toolbar"
        :aria-label="t('editor.label.toolbar')"
        class="flex flex-wrap items-center gap-2 p-2"
    >
        <EditorFormattingControls :editor="editor" /><Separator
            orientation="vertical"
            class="h-6"
        /><EditorBlockControls :editor="editor" /><EditorTableControls
            :editor="editor"
        />
        <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            :disabled="!editor.isEditable"
            :aria-label="t('editor.button.link')"
            data-test="editor-link-menu"
            :title="t('editor.button.link')"
            @mousedown.prevent
            @click="$emit('link')"
            ><Link
        /></Button>
    </div>
</template>

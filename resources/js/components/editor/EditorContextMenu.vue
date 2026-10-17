<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3';

import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuGroup,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import { useTranslations } from '@/composables/useTranslations';
import {
    canRunEditorTableAction,
    editorTableActions,
    runEditorAction,
} from '@/lib/editor';
import type { EditorAction, TranslationKey } from '@/types';

defineProps<{ editor: Editor }>();

defineEmits<{ link: [] }>();

const { t } = useTranslations();

const actions: { action: EditorAction; label: TranslationKey }[] = [
    { action: 'bold', label: 'editor.button.bold' },
    { action: 'italic', label: 'editor.button.italic' },
    { action: 'bulletList', label: 'editor.button.bullet_list' },
    { action: 'orderedList', label: 'editor.button.ordered_list' },
    { action: 'blockquote', label: 'editor.button.quote' },
    { action: 'codeBlock', label: 'editor.button.code' },
    { action: 'clear', label: 'editor.button.clear' },
];
</script>
<template>
    <ContextMenu>
        <ContextMenuTrigger
            as-child
            :disabled="!editor.isEditable"
        >
            <div><slot /></div>
        </ContextMenuTrigger>
        <ContextMenuContent>
            <ContextMenuGroup>
                <ContextMenuItem
                    v-for="item in actions"
                    :key="item.action"
                    :data-test="`editor-context-${item.action}`"
                    @select="runEditorAction(editor, item.action)"
                >
                    {{ t(item.label) }}
                </ContextMenuItem>
                <ContextMenuItem @select="$emit('link')">
                    {{ t('editor.button.link') }}
                </ContextMenuItem>
                <ContextMenuItem
                    :disabled="!editor.isActive('link')"
                    @select="editor.chain().focus().unsetLink().run()"
                >
                    {{ t('editor.button.unlink') }}
                </ContextMenuItem>
            </ContextMenuGroup>
            <ContextMenuSeparator />
            <ContextMenuGroup>
                <ContextMenuSub>
                    <ContextMenuSubTrigger data-test="editor-context-table">
                        {{ t('editor.label.table') }}
                    </ContextMenuSubTrigger>
                    <ContextMenuSubContent>
                        <ContextMenuGroup>
                            <ContextMenuItem
                                v-for="item in editorTableActions"
                                :key="item.action"
                                :disabled="
                                    !canRunEditorTableAction(
                                        editor,
                                        item.action,
                                    )
                                "
                                :data-test="`editor-context-${item.action}`"
                                @select="runEditorAction(editor, item.action)"
                            >
                                {{ t(item.label) }}
                            </ContextMenuItem>
                        </ContextMenuGroup>
                    </ContextMenuSubContent>
                </ContextMenuSub>
            </ContextMenuGroup>
            <ContextMenuSeparator />
            <ContextMenuGroup>
                <ContextMenuItem
                    :disabled="!editor.can().undo()"
                    @select="runEditorAction(editor, 'undo')"
                >
                    {{ t('editor.button.undo') }}
                </ContextMenuItem>
                <ContextMenuItem
                    :disabled="!editor.can().redo()"
                    @select="runEditorAction(editor, 'redo')"
                >
                    {{ t('editor.button.redo') }}
                </ContextMenuItem>
            </ContextMenuGroup>
        </ContextMenuContent>
    </ContextMenu>
</template>

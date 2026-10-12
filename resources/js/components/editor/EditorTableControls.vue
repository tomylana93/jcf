<script setup lang="ts">
import { Table2 } from '@lucide/vue';
import type { Editor } from '@tiptap/vue-3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import {
    editorTableActions,
    canRunEditorTableAction,
    runEditorAction,
} from '@/lib/editor';
defineProps<{ editor: Editor }>();
const { t } = useTranslations();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="!editor.isEditable"
                :aria-label="t('editor.label.table')"
                :title="t('editor.label.table')"
                data-test="editor-table-menu"
                @mousedown.prevent
                ><Table2
            /></Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start">
            <DropdownMenuGroup>
                <DropdownMenuItem
                    v-for="item in editorTableActions"
                    :key="item.action"
                    :disabled="!canRunEditorTableAction(editor, item.action)"
                    :data-test="`editor-table-${item.action}`"
                    @select="runEditorAction(editor, item.action)"
                    >{{ t(item.label) }}</DropdownMenuItem
                >
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

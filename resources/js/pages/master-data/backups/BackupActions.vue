<script setup lang="ts">
import { Download, Ellipsis, RotateCcw, Trash2 } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import { download } from '@/routes/master-data/backups';
import type { BackupArchive } from '@/types';
defineProps<{
    backup: BackupArchive;
    disabled: boolean;
    deleteDisabled: boolean;
}>();
const emit = defineEmits<{ restore: []; delete: [] }>();
const { t } = useTranslations();
</script>
<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                :aria-label="t('table.label.actions')"
                ><Ellipsis aria-hidden="true"
            /></Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuGroup>
                <DropdownMenuItem as-child
                    ><a :href="download.url(backup.filename)"
                        ><Download aria-hidden="true" />{{
                            t('backup.button.download')
                        }}</a
                    ></DropdownMenuItem
                >
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    variant="destructive"
                    :disabled="disabled"
                    @select="emit('restore')"
                    ><RotateCcw aria-hidden="true" />{{
                        t('backup.button.restore')
                    }}</DropdownMenuItem
                >
                <DropdownMenuItem
                    variant="destructive"
                    :disabled="deleteDisabled"
                    @select="emit('delete')"
                    ><Trash2 aria-hidden="true" />{{
                        t('common.button.delete')
                    }}</DropdownMenuItem
                >
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

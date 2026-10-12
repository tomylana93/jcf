<script setup lang="ts">
import { Ban, CircleCheck, Ellipsis, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuGroup,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        canChangeStatus?: boolean;
        canDelete?: boolean;
        active: boolean;
        recordName?: string;
    }>(),
    {
        canChangeStatus: true,
        canDelete: false,
        recordName: '',
    },
);

const emit = defineEmits<{ edit: []; changeStatus: []; delete: [] }>();

const { t } = useTranslations();

const dialogOpen = ref(false);

const pendingAction = ref<'status' | 'delete' | null>(null);

const actionLabel = computed(() =>
    t(
        pendingAction.value === 'delete'
            ? 'table.button.delete'
            : props.active
              ? 'common.button.disable'
              : 'common.button.activate',
    ),
);

const description = computed(() =>
    t(
        pendingAction.value === 'delete'
            ? 'table.helper.delete'
            : props.active
              ? 'table.helper.disable'
              : 'table.helper.activate',
        { name: props.recordName || t('table.helper.record') },
    ),
);

function requestConfirmation(action: 'status' | 'delete'): void {
    pendingAction.value = action;
    dialogOpen.value = true;
}

function confirmAction(): void {
    if (pendingAction.value === 'delete' && props.canDelete) {
        emit('delete');
    } else if (pendingAction.value === 'status' && props.canChangeStatus) {
        emit('changeStatus');
    }
    pendingAction.value = null;
}
</script>

<template>
    <AlertDialog v-model:open="dialogOpen">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="t('table.label.actions')"
                >
                    <Ellipsis aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                @close-auto-focus="dialogOpen && $event.preventDefault()"
            >
                <DropdownMenuGroup>
                    <DropdownMenuItem @select="emit('edit')">
                        <Pencil aria-hidden="true" />
                        {{ t('table.button.edit') }}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator
                        v-if="canChangeStatus || canDelete"
                    />
                    <DropdownMenuItem
                        v-if="canChangeStatus"
                        :variant="active ? 'destructive' : 'default'"
                        @select="requestConfirmation('status')"
                    >
                        <Ban v-if="active" aria-hidden="true" />
                        <CircleCheck v-else aria-hidden="true" />
                        {{
                            t(
                                active
                                    ? 'common.button.disable'
                                    : 'common.button.activate',
                            )
                        }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-if="canDelete"
                        variant="destructive"
                        @select="requestConfirmation('delete')"
                    >
                        <Trash2 aria-hidden="true" />
                        {{ t('table.button.delete') }}
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{
                    t('table.helper.confirmation_title', {
                        action: actionLabel,
                    })
                }}</AlertDialogTitle>
                <AlertDialogDescription>{{
                    description
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel data-test="cancel-row-action">{{
                    t('common.button.cancel')
                }}</AlertDialogCancel>
                <AlertDialogAction
                    data-test="confirm-row-action"
                    @click="confirmAction"
                    >{{ actionLabel }}</AlertDialogAction
                >
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

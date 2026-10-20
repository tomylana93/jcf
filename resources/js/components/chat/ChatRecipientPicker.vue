<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { onScopeDispose, ref, watch } from 'vue';

import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { recipients } from '@/routes/chat';
import type { ChatPage, ChatUser } from '@/types/chat';
const open = defineModel<boolean>({ required: true });
defineProps<{ busy: boolean; failed: boolean }>();
defineEmits<{ select: [id: string] }>();
const { t } = useTranslations();
const search = ref('');
const users = ref<ChatUser[]>([]);
const nextCursor = ref<string | null>(null);
const searchFailed = ref(false);
const http = useHttp<Record<string, never>, ChatPage<ChatUser>>({});
let version = 0;
let timer: ReturnType<typeof setTimeout> | undefined;
async function load(more = false): Promise<void> {
    const current = ++version;
    http.cancel();
    searchFailed.value = false;
    try {
        const result = await http.get(
            recipients.url({
                query: {
                    search: search.value,
                    ...(more && nextCursor.value
                        ? { cursor: nextCursor.value }
                        : {}),
                },
            }),
        );
        if (current !== version) return;
        users.value = more ? [...users.value, ...result.data] : result.data;
        nextCursor.value = result.next_cursor;
    } catch {
        if (current === version) searchFailed.value = true;
    }
}
watch([search, open], () => {
    clearTimeout(timer);
    version++;
    http.cancel();
    users.value = [];
    nextCursor.value = null;
    if (open.value) timer = setTimeout(() => void load(), 250);
});
onScopeDispose(() => {
    version++;
    clearTimeout(timer);
    http.cancel();
});
</script>
<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('chat.button.new') }}</DialogTitle>
                <DialogDescription>
                    {{ t('chat.helper.recipients') }}
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <Label for="chat-recipient-search">
                    {{ t('chat.label.search') }}
                </Label>
                <Input
                    id="chat-recipient-search"
                    v-model="search"
                    :maxlength="100"
                    autocomplete="off"
                />
            </div>
            <p
                v-if="searchFailed || failed"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ t('chat.error.recipients') }}
                <Button
                    variant="link"
                    @click="load()"
                >
                    {{ t('chat.button.retry') }}
                </Button>
            </p>
            <p
                v-if="http.processing"
                role="status"
                class="text-sm text-muted-foreground"
            >
                {{ t('chat.status.loading') }}
            </p>
            <div
                class="max-h-80 overflow-y-auto"
                :aria-label="t('chat.label.recipients')"
            >
                <Button
                    v-for="user in users"
                    :key="user.id"
                    variant="ghost"
                    class="h-auto w-full justify-start gap-3 py-3"
                    :disabled="busy"
                    @click="$emit('select', user.id)"
                >
                    <ChatAvatar :user="user" />
                    <span class="truncate">{{ user.name }}</span>
                </Button>
                <p
                    v-if="!http.processing && !searchFailed && !users.length"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('chat.status.no_recipients') }}
                </p>
            </div>
            <Button
                v-if="nextCursor"
                variant="outline"
                :disabled="http.processing"
                @click="load(true)"
            >
                {{ t('chat.button.more') }}
            </Button>
        </DialogContent>
    </Dialog>
</template>

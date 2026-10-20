<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { ArrowLeft, MessageSquarePlus, RefreshCw, Send } from '@lucide/vue';
import { computed, onMounted, ref, watchEffect } from 'vue';

import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import ChatHistory from '@/components/chat/ChatHistory.vue';
import ChatRecipientPicker from '@/components/chat/ChatRecipientPicker.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { useChat } from '@/composables/useChat';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/chat';

const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const page = usePage();
const actor = computed(() => ({
    id: page.props.auth.user.id,
    name: page.props.auth.user.name,
    avatar: page.props.auth.user.avatar ?? null,
}));
const {
    conversations,
    selectedId,
    selected,
    messages,
    draft,
    outgoing,
    inboxCursor,
    hasOlder,
    inboxError,
    historyError,
    startError,
    readError,
    inboxHttp,
    historyHttp,
    sendHttp,
    startHttp,
    readHttp,
    loadInbox,
    loadHistory,
    select,
    start,
    submit,
    transmit,
    markRead,
} = useChat();
const pickerOpen = ref(false);
const composing = ref(false);
const length = computed(() => Array.from(draft.value).length);
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('chat.title'), href: index() }],
    }),
);
onMounted(() => void loadInbox());
async function choose(id: string): Promise<void> {
    if (await start(id)) pickerOpen.value = false;
}
function keydown(event: KeyboardEvent): void {
    if (
        event.key === 'Enter' &&
        !event.shiftKey &&
        !event.isComposing &&
        !composing.value &&
        event.keyCode !== 229
    ) {
        event.preventDefault();
        submit();
    }
}
</script>
<template>
    <Head :title="t('chat.title')" />

    <PageContainer :title="t('chat.title')">
        <div
            class="grid h-[calc(100dvh-15rem)] min-h-80 min-w-0 overflow-hidden rounded-xl border bg-background md:h-[calc(100dvh-12rem)] md:grid-cols-[18rem_minmax(0,1fr)]"
            data-test="chat-workspace"
        >
            <section
                :class="[
                    'min-h-0 flex-col md:flex md:border-r',
                    selected ? 'hidden' : 'flex',
                ]"
                :aria-label="t('chat.label.inbox')"
            >
                <div
                    class="flex items-center justify-between gap-2 border-b p-3"
                >
                    <h2 class="font-medium">{{ t('chat.label.inbox') }}</h2>
                    <div class="flex gap-1">
                        <Button
                            size="icon"
                            variant="ghost"
                            :aria-label="t('chat.button.refresh')"
                            :disabled="inboxHttp.processing"
                            @click="loadInbox()"
                        >
                            <RefreshCw aria-hidden="true" />
                        </Button>
                        <Button
                            size="icon"
                            variant="ghost"
                            :aria-label="t('chat.button.new')"
                            @click="pickerOpen = true"
                        >
                            <MessageSquarePlus aria-hidden="true" />
                        </Button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-2">
                    <p
                        v-if="inboxError"
                        role="alert"
                        class="p-3 text-sm text-destructive"
                    >
                        {{ t('chat.error.inbox') }}
                        <Button
                            variant="link"
                            @click="loadInbox()"
                        >
                            {{ t('chat.button.retry') }}
                        </Button>
                    </p>
                    <div
                        v-if="inboxHttp.processing && !conversations.length"
                        role="status"
                        class="space-y-3 p-3"
                    >
                        <span class="sr-only">
                            {{ t('chat.status.loading') }}
                        </span>
                        <Skeleton
                            v-for="n in 3"
                            :key="n"
                            class="h-14 w-full"
                        />
                    </div>
                    <p
                        v-else-if="!conversations.length && !inboxError"
                        class="px-3 py-8 text-sm text-muted-foreground"
                    >
                        {{ t('chat.status.empty_inbox') }}
                    </p>
                    <button
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        type="button"
                        :aria-pressed="selectedId === conversation.id"
                        :class="[
                            'flex w-full items-center gap-3 rounded-lg p-3 text-left outline-none hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring',
                            selectedId === conversation.id && 'bg-muted',
                        ]"
                        @click="select(conversation)"
                    >
                        <ChatAvatar :user="conversation.recipient" />
                        <span class="flex min-w-0 flex-1 flex-col gap-1">
                            <span class="truncate text-sm font-medium">
                                {{ conversation.recipient.name }}
                            </span>
                            <span
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{
                                    conversation.last_message?.body ??
                                    t('chat.status.empty_history')
                                }}
                            </span>
                            <time
                                v-if="conversation.last_message"
                                class="text-xs text-muted-foreground"
                                :datetime="conversation.last_message.created_at"
                            >
                                {{
                                    formatDateTime(
                                        conversation.last_message.created_at,
                                    )
                                }}
                            </time>
                        </span>
                        <Badge
                            v-if="conversation.unread_count"
                            :aria-label="
                                t('chat.label.unread', {
                                    count: conversation.unread_count,
                                })
                            "
                        >
                            {{ conversation.unread_count }}
                        </Badge>
                    </button>
                    <Button
                        v-if="inboxCursor"
                        variant="ghost"
                        class="w-full"
                        :disabled="inboxHttp.processing"
                        @click="loadInbox(true)"
                    >
                        {{ t('chat.button.more') }}
                    </Button>
                </div>
            </section>
            <section
                :class="[
                    'min-h-0 min-w-0 flex-col md:flex',
                    selected ? 'flex' : 'hidden',
                ]"
                :aria-label="t('chat.label.conversation')"
            >
                <template v-if="selected">
                    <div class="flex min-w-0 items-center gap-2 border-b p-3">
                        <Button
                            size="icon"
                            variant="ghost"
                            class="md:hidden"
                            :aria-label="t('chat.button.back')"
                            @click="selectedId = null"
                        >
                            <ArrowLeft aria-hidden="true" />
                        </Button>
                        <ChatAvatar :user="selected.recipient" />
                        <h2 class="min-w-0 flex-1 truncate text-sm font-medium">
                            {{ selected.recipient.name }}
                        </h2>
                        <Button
                            size="icon"
                            variant="ghost"
                            :aria-label="t('chat.button.refresh_history')"
                            :disabled="historyHttp.processing"
                            @click="loadHistory()"
                        >
                            <RefreshCw aria-hidden="true" />
                        </Button>
                    </div>
                    <div
                        v-if="historyHttp.processing && !messages.length"
                        role="status"
                        class="flex-1 space-y-4 p-6"
                    >
                        <span class="sr-only">
                            {{ t('chat.status.loading') }}
                        </span>
                        <Skeleton class="h-12 w-2/3" />
                        <Skeleton class="ml-auto h-12 w-1/2" />
                    </div>
                    <p
                        v-if="historyError"
                        role="alert"
                        class="p-3 text-sm text-destructive"
                    >
                        {{ t('chat.error.history') }}
                        <Button
                            variant="link"
                            @click="loadHistory()"
                        >
                            {{ t('chat.button.retry') }}
                        </Button>
                    </p>
                    <Button
                        v-if="hasOlder"
                        variant="ghost"
                        class="shrink-0 self-center"
                        :disabled="historyHttp.processing"
                        @click="loadHistory(true)"
                    >
                        {{ t('chat.button.older') }}
                    </Button>
                    <p
                        v-if="
                            !historyHttp.processing &&
                            !historyError &&
                            !messages.length &&
                            !outgoing.length
                        "
                        class="p-6 text-sm text-muted-foreground"
                    >
                        {{ t('chat.status.empty_history') }}
                    </p>
                    <div class="min-h-0 flex-1">
                        <ChatHistory
                            :key="selected.id"
                            :conversation="selected"
                            :messages="messages"
                            :pending="outgoing"
                            :actor="actor"
                            :busy="sendHttp.processing"
                            @retry="transmit(selected!.id, $event)"
                        />
                    </div>
                    <div
                        class="flex shrink-0 flex-wrap items-center gap-2 border-t px-3 py-1"
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="
                                !messages.length ||
                                readHttp.processing ||
                                historyHttp.processing
                            "
                            @click="markRead()"
                        >
                            {{ t('chat.button.read') }}
                        </Button>
                        <span
                            v-if="readError"
                            role="alert"
                            class="text-xs text-destructive"
                        >
                            {{ t('chat.error.read') }}
                        </span>
                    </div>
                    <form
                        class="shrink-0 p-3 pt-0"
                        @submit.prevent="submit"
                    >
                        <p
                            v-if="!selected.can_send"
                            role="status"
                            class="pb-2 text-sm text-muted-foreground"
                        >
                            {{ t('chat.status.unavailable') }}
                        </p>
                        <Label
                            for="chat-body"
                            class="sr-only"
                        >
                            {{ t('chat.label.message') }}
                        </Label>
                        <InputGroup>
                            <InputGroupTextarea
                                id="chat-body"
                                v-model="draft"
                                :disabled="!selected.can_send"
                                :placeholder="t('chat.placeholder.message')"
                                :aria-describedby="
                                    length > 4000
                                        ? 'chat-length-error'
                                        : 'chat-compose-help'
                                "
                                :aria-invalid="length > 4000"
                                rows="2"
                                class="max-h-40 min-h-16"
                                @keydown="keydown"
                                @compositionstart="composing = true"
                                @compositionend="composing = false"
                            />
                            <InputGroupAddon
                                align="block-end"
                                class="justify-between gap-2"
                            >
                                <span
                                    id="chat-compose-help"
                                    class="text-xs font-normal"
                                >
                                    {{ t('chat.helper.keyboard') }}
                                </span>
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="
                                        !draft.trim() ||
                                        length > 4000 ||
                                        sendHttp.processing ||
                                        !selected.can_send
                                    "
                                >
                                    <Send aria-hidden="true" />
                                    {{ t('chat.button.send') }}
                                </Button>
                            </InputGroupAddon>
                        </InputGroup>
                        <p
                            v-if="length > 4000"
                            id="chat-length-error"
                            role="alert"
                            class="pt-1 text-xs text-destructive"
                        >
                            {{ t('chat.error.length') }}
                        </p>
                    </form>
                </template>
                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-4 p-8 text-center"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ t('chat.status.select') }}
                    </p>
                    <Button
                        variant="outline"
                        @click="pickerOpen = true"
                    >
                        {{ t('chat.button.new') }}
                    </Button>
                </div>
            </section>
        </div>
        <ChatRecipientPicker
            v-model="pickerOpen"
            :busy="startHttp.processing"
            :failed="startError"
            @select="choose"
        />
    </PageContainer>
</template>

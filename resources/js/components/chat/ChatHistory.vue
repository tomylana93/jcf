<script setup lang="ts">
import { ArrowDown } from '@lucide/vue';

import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { Button } from '@/components/ui/button';
import {
    Message,
    MessageAvatar,
    MessageContent,
    MessageFooter,
    MessageHeader,
} from '@/components/ui/message';
import {
    MessageScroller,
    MessageScrollerButton,
    MessageScrollerContent,
    MessageScrollerItem,
    MessageScrollerProvider,
    MessageScrollerViewport,
} from '@/components/ui/message-scroller';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import type {
    ChatConversation,
    ChatMessage,
    ChatUser,
    PendingChatMessage,
} from '@/types/chat';

defineProps<{
    conversation: ChatConversation;
    messages: ChatMessage[];
    pending: PendingChatMessage[];
    actor: ChatUser;
    busy: boolean;
}>();
defineEmits<{ retry: [message: PendingChatMessage] }>();
const { t } = useTranslations();
const { formatDateTime } = useDateTime();
</script>
<template>
    <MessageScrollerProvider
        :auto-scroll="true"
        default-scroll-position="end"
    >
        <MessageScroller>
            <MessageScrollerViewport
                :aria-label="t('chat.label.history')"
                class="p-4 [overflow-anchor:none] md:p-6"
            >
                <MessageScrollerContent
                    class="gap-5"
                    :aria-label="t('chat.label.history')"
                    aria-live="polite"
                >
                    <MessageScrollerItem
                        v-for="message in messages"
                        :key="message.id"
                        :message-id="message.id"
                        class="[content-visibility:visible]"
                    >
                        <Message
                            :align="
                                message.sender_id === actor.id ? 'end' : 'start'
                            "
                        >
                            <MessageAvatar>
                                <ChatAvatar
                                    :user="
                                        message.sender_id === actor.id
                                            ? actor
                                            : conversation.recipient
                                    "
                                />
                            </MessageAvatar>
                            <MessageContent>
                                <MessageHeader>
                                    {{
                                        message.sender_id === actor.id
                                            ? actor.name
                                            : conversation.recipient.name
                                    }}
                                </MessageHeader>
                                <Bubble
                                    :variant="
                                        message.sender_id === actor.id
                                            ? 'default'
                                            : 'muted'
                                    "
                                    :align="
                                        message.sender_id === actor.id
                                            ? 'end'
                                            : 'start'
                                    "
                                >
                                    <BubbleContent
                                        class="wrap-anywhere whitespace-pre-wrap"
                                        data-test="chat-message-body"
                                    >
                                        {{ message.body }}
                                    </BubbleContent>
                                </Bubble>
                                <MessageFooter>
                                    <time :datetime="message.created_at">
                                        {{ formatDateTime(message.created_at) }}
                                    </time>
                                    <span
                                        v-if="message.sender_id === actor.id"
                                        class="ml-2"
                                    >
                                        {{ t('chat.status.saved') }}
                                    </span>
                                </MessageFooter>
                            </MessageContent>
                        </Message>
                    </MessageScrollerItem>
                    <MessageScrollerItem
                        v-for="message in pending"
                        :key="message.client_message_id"
                        :message-id="message.client_message_id"
                        class="[content-visibility:visible]"
                    >
                        <Message align="end">
                            <MessageContent>
                                <Bubble
                                    align="end"
                                    variant="outline"
                                >
                                    <BubbleContent
                                        class="wrap-anywhere whitespace-pre-wrap"
                                    >
                                        {{ message.body }}
                                    </BubbleContent>
                                </Bubble>
                                <MessageFooter
                                    class="gap-2"
                                    aria-live="polite"
                                >
                                    <span>
                                        {{
                                            t(
                                                message.status === 'sending'
                                                    ? 'chat.status.sending'
                                                    : 'chat.error.send',
                                            )
                                        }}
                                    </span>
                                    <Button
                                        v-if="message.status === 'failed'"
                                        size="sm"
                                        variant="outline"
                                        :disabled="busy"
                                        @click="$emit('retry', message)"
                                    >
                                        {{ t('chat.button.retry') }}
                                    </Button>
                                </MessageFooter>
                            </MessageContent>
                        </Message>
                    </MessageScrollerItem>
                </MessageScrollerContent>
            </MessageScrollerViewport>
            <MessageScrollerButton :aria-label="t('chat.button.latest')">
                <ArrowDown aria-hidden="true" />
                <span class="sr-only">{{ t('chat.button.latest') }}</span>
            </MessageScrollerButton>
        </MessageScroller>
    </MessageScrollerProvider>
</template>

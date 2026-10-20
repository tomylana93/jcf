export type ChatUser = { id: string; name: string; avatar: string | null };
export type ChatMessage = {
    id: string;
    conversation_id: string;
    sender_id: string;
    sequence: number;
    client_message_id: string;
    body: string;
    created_at: string;
};
export type ChatConversation = {
    id: string;
    recipient: ChatUser;
    last_message: ChatMessage | null;
    last_message_sequence: number;
    last_read_sequence: number;
    recipient_last_read_sequence: number;
    unread_count: number;
    can_send: boolean;
};
export type ChatPage<T> = { data: T[]; next_cursor: string | null };
export type ChatHistory = { data: ChatMessage[]; has_more: boolean };
export type ChatReadEvent = {
    conversation_id: string;
    user_id: string;
    last_read_sequence: number;
};
export type ChatTypingEvent = {
    conversation_id: string;
    user_id: string;
    typing: boolean;
    expires_at: string;
};
export type ChatSummary = { unread_count: number };
export type ChatRealtimeState = 'connecting' | 'connected' | 'disconnected';
export type PendingChatMessage = {
    client_message_id: string;
    body: string;
    created_at: string;
    status: 'sending' | 'failed';
};

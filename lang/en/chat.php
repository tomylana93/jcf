<?php

declare(strict_types=1);

return [
    'title' => 'Chat',

    'label' => [
        'inbox' => 'Conversations',
        'conversation' => 'Conversation',
        'history' => 'Message history',
        'search' => 'Search by name',
        'recipients' => 'Available recipients',
        'message' => 'Message',
        'unread' => ':count unread messages',
    ],

    'placeholder' => [
        'message' => 'Write a message…',
    ],

    'helper' => [
        'keyboard' => 'Enter to send · Shift+Enter for a new line',
        'recipients' => 'Start a private conversation with an active chat user.',
    ],

    'button' => [
        'new' => 'New conversation',
        'refresh' => 'Refresh conversations',
        'refresh_history' => 'Refresh messages',
        'back' => 'Back to conversations',
        'send' => 'Send',
        'retry' => 'Retry',
        'more' => 'Load more',
        'older' => 'Load older messages',
        'latest' => 'Go to latest message',
        'read' => 'Mark loaded messages as read',
    ],

    'status' => [
        'loading' => 'Loading…',
        'empty_inbox' => 'No conversations yet. Start a new conversation.',
        'empty_history' => 'No messages yet.',
        'select' => 'Select a conversation to read and send messages.',
        'no_recipients' => 'No available recipients found.',
        'saved' => 'Saved',
        'sending' => 'Sending…',
        'unavailable' => 'Messages cannot be sent while a participant has no chat access.',
    ],

    'error' => [
        'inbox' => 'Conversations could not be loaded.',
        'history' => 'Messages could not be loaded.',
        'recipients' => 'Recipients could not be loaded or the conversation could not be started.',
        'send' => 'Message could not be confirmed. Retry to check or send it.',
        'read' => 'Read status could not be saved. Try again.',
        'length' => 'Messages must contain no more than 4,000 characters.',
        'self_conversation' => 'A direct conversation requires a different recipient.',
        'idempotency_conflict' => 'The message identifier has already been used for a different message.',
    ],
];

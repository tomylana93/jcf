<?php

declare(strict_types=1);

return [
    'title' => [
        'public' => 'Contact',
        'form' => 'Send a message',
        'inbox' => 'Contact inbox',
        'detail' => 'Contact message',
        'notification' => 'New contact message',
    ],
    'label' => [
        'name' => 'Name',
        'email' => 'Email',
        'subject' => 'Subject',
        'message' => 'Message',
        'received' => 'Received',
        'status' => 'Status',
        'website_url' => 'Leave this field empty',
    ],
    'helper' => [
        'form' => 'Send an enquiry using the form. All fields are required.',
        'privacy' => 'Contact details are used to respond to this enquiry.',
        'inbox' => 'Messages submitted through the public contact form.',
        'notification' => 'A new message is available in the protected contact inbox.',
    ],
    'button' => [
        'send' => 'Send message',
        'sending' => 'Sending…',
        'open' => 'Open inbox',
        'handled' => 'Mark handled',
        'unhandled' => 'Mark unhandled',
        'back' => 'Back to inbox',
    ],
    'status' => [
        'sent' => 'Message received. Thank you for the enquiry.',
        'handled' => 'Handled',
        'unhandled' => 'Unhandled',
    ],
    'error' => [
        'invalid' => 'Check the highlighted fields and try again.',
        'throttled' => 'Too many attempts. Wait one minute and try again.',
        'session' => 'The session has expired. Reload this page before trying again.',
        'server' => 'The message could not be sent. Input is retained; try again shortly.',
        'network' => 'Connection failed. Check the connection and try again.',
    ],
];

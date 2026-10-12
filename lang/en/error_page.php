<?php

declare(strict_types=1);

return [
    '403' => [
        'title' => 'Access denied',
        'description' => 'This page is not available with the current permissions.',
    ],
    '404' => [
        'title' => 'Not found',
        'description' => 'The page may have moved or the address may be incorrect.',
    ],
    '419' => [
        'title' => 'Session expired',
        'description' => 'Reload the page to start a new session.',
    ],
    '500' => [
        'title' => 'Something went wrong',
        'description' => 'An unexpected problem occurred. Please try again.',
    ],
    '503' => [
        'title' => 'Service unavailable',
        'description' => 'The service is temporarily unavailable. Please try again later.',
    ],
    'action' => [
        'retry' => 'Reload page',
        'home' => 'Go to home',
    ],
];

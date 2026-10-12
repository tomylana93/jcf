<?php

declare(strict_types=1);

return [
    'title' => 'Documentation',

    'card' => [
        'manage_title' => 'Manage Documentation',
        'edit_title' => 'Edit Documentation',
        'create_title' => 'Create Documentation',
        'description' => 'Guides and reference for the application.',
    ],

    'label' => [
        'title' => 'Title',
        'slug' => 'Slug (optional)',
        'summary' => 'Summary',
        'content' => 'Content',
        'status' => 'Status',
        'search' => 'Search documentation',
    ],

    'placeholder' => [
        'slug' => 'Automatically generated from the title when empty',
        'search' => 'Search titles, summaries, and content',
    ],

    'helper' => [
        'empty' => 'No documentation found.',
        'unsaved' => 'Discard unsaved changes?',
        'delete' => 'Permanently delete this documentation?',
    ],

    'button' => [
        'manage' => 'Manage Documentation',
        'read' => 'Read',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'preview' => 'Preview',
        'back' => 'Back to documentation',
        'previous' => 'Previous',
        'next' => 'Next',
        'search' => 'Search',
    ],

    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'saved' => 'Documentation saved.',
        'deleted' => 'Documentation deleted.',
    ],

    'error' => [
        'invalid_content' => 'Content contains unsupported formatting, unsafe links, or exceeds editor limits.',
    ],
];

<?php

declare(strict_types=1);

return [
    'title' => 'Documentation',

    'card' => [
        'manage_title' => 'Manage documentation',
        'edit_title' => 'Edit documentation',
        'create_title' => 'Create documentation',
        'description' => 'Guides and reference for the application.',
    ],

    'label' => [
        'title' => 'Title',
        'slug' => 'Slug (optional)',
        'summary' => 'Summary',
        'content' => 'Content',
        'status' => 'Status',
        'search' => 'Search documentation',
        'pagination' => 'Documentation pages',
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
        'manage' => 'Manage documentation',
        'read' => 'Read',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'preview' => 'Preview',
        'back' => 'Back to documentation',
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

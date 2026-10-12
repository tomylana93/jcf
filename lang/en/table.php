<?php

declare(strict_types=1);

return [
    'label' => [
        'select_visible' => 'Select all visible rows',
        'select_row' => 'Select row',
        'actions' => 'Actions',
        'search' => 'Search data',
    ],

    'placeholder' => [
        'all' => 'All',
        'search' => 'Search data...',
    ],

    'helper' => [
        'confirmation_title' => ':action confirmation',
        'record' => 'this record',
        'disable' => 'Disable :name?',
        'activate' => 'Activate :name?',
        'delete' => 'Delete :name? This action cannot be undone.',
        'selected_count' => ':count rows selected',
        'empty' => 'No data found',
        'empty_description' => 'Try another search or adjust the filters.',
    ],

    'button' => [
        'columns' => 'Columns',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    'export' => [
        'label' => [
            'excel' => 'Excel',
            'pdf' => 'PDF',
        ],

        'helper' => [
            'scope' => 'Selected rows or all filtered records; visible columns.',
        ],

        'button' => [
            'export' => 'Export',
            'download' => 'Download :format',
        ],

        'status' => [
            'preparing' => 'Preparing :format…',
            'ready' => ':format is ready.',
        ],

        'error' => [
            'limit' => 'This export exceeds 1,000 rows. Adjust the filters to export fewer records.',
            'failed' => 'Export failed. Try again.',
        ],
    ],

    'pagination' => [
        'label' => [
            'rows_per_page' => 'Rows per page',
        ],

        'helper' => [
            'summary' => 'Showing :from–:to of :total records',
            'page_status' => 'Page :current of :total',
        ],

        'button' => [
            'first' => 'First page',
            'previous' => 'Previous page',
            'next' => 'Next page',
            'last' => 'Last page',
        ],
    ],
];

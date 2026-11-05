<?php

declare(strict_types=1);

return [
    'title' => 'API clients',

    'card' => [
        'title' => 'API clients',
        'description' => 'Partner systems that call the external API with their own tokens.',
        'create_title' => 'Add API client',
        'edit_title' => 'Edit API client',
        'tokens' => 'Client tokens',
        'tokens_description' => 'Tokens issued to this client. Each token is shown once when created.',
    ],

    'label' => [
        'name' => 'Client name',
        'description' => 'Description',
        'status' => 'Status',
        'tokens' => 'Tokens',
        'created_at' => 'Created',
    ],

    'placeholder' => [
        'name' => 'e.g., Logistics partner',
        'description' => 'What this client uses the API for',
    ],

    'helper' => [
        'disabled' => 'Requests from a disabled client are rejected. Its tokens are kept and work again after reactivation.',
    ],

    'status' => [
        'created' => 'API client created.',
        'updated' => 'API client updated.',
    ],

    'error' => [
        'wrong_owner' => 'This token cannot be used for this endpoint.',
        'disabled' => 'The API client is disabled.',
    ],
];

<?php

declare(strict_types=1);

return [
    'title' => 'Users',

    'card' => [
        'title' => 'Users',
        'description' => 'Manage user accounts and access permissions.',
        'create_title' => 'Add user',
        'edit_title' => 'Edit user',
    ],

    'label' => [
        'name' => 'Full name',
        'email' => 'Email address',
        'role' => 'Role',
        'no_role' => 'No role',
        'status' => 'Status',
        'created_at' => 'Created',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'current_password' => 'Current password',
        'new_password' => 'New password',
    ],

    'placeholder' => [
        'name' => 'Enter full name',
        'email' => 'Enter email address',
        'role' => 'Select role',
        'status' => 'Select status',
        'password' => 'Enter password',
        'password_confirmation' => 'Enter password again',
        'current_password' => 'Enter current password',
        'new_password' => 'Enter new password',
    ],

    'helper' => [
        'email_verified' => 'Verified',
    ],

    'status' => [
        'created' => 'User created. The invitation has been queued for email delivery.',
        'updated' => 'User updated.',
        'activated' => 'User activated.',
        'disabled' => 'User disabled.',
        'invitation_queued' => 'The invitation has been queued for email delivery.',
    ],

    'invitation' => [
        'create_hint' => 'A link to set a password and verify the email address will be sent automatically.',
        'pending' => 'The invitation has not been accepted.',
        'resend' => 'Resend invitation',
        'subject' => 'Account invitation',
        'description' => 'An account has been created by an administrator. Use the following link to set a password.',
        'action' => 'Set password',
        'expires' => 'This link expires in :minutes minutes.',
        'verification' => 'The email address is automatically verified after the password is set successfully.',
        'unavailable' => 'Invitations are only available for active accounts with a pending invitation.',
    ],
];

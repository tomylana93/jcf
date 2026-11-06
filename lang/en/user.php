<?php

declare(strict_types=1);

return [
    'title' => 'Users',

    'card' => [
        'title' => 'Users',
        'description' => 'Accounts, roles, and invitations for people who use the application.',
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
        'send_invitation' => 'The user sets a password through a link sent by email.',
    ],

    'status' => [
        'created' => 'User created. An invitation email will be sent.',
        'updated' => 'User updated.',
        'activated' => 'User activated.',
        'disabled' => 'User disabled.',
        'invitation_queued' => 'The invitation email will be sent.',
    ],

    'invitation' => [
        'pending' => 'The invitation has not been accepted.',
        'resend' => 'Resend invitation',
        'subject' => 'Account invitation',
        'description' => 'An account has been created by an administrator. Use the following link to set a password.',
        'action' => 'Set password',
        'expires' => 'This link expires in :minutes minutes.',
        'verification' => 'The email address is automatically verified after the password is set.',
        'unavailable' => 'Invitations are only available for active accounts with a pending invitation.',
    ],
];

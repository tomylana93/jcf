<?php

declare(strict_types=1);

return [
    'card' => [
        'title' => 'Account settings',
        'description' => 'Manage profile and account settings.',
    ],
    'link' => [
        'profile' => 'Profile',
        'security' => 'Security',
    ],

    'profile' => [
        'title' => 'Profile settings',
        'card' => [
            'title' => 'Profile',
            'description' => 'Update the account name and email address.',
        ],
        'link' => [
            'resend' => 'Resend the verification email.',
        ],
        'label' => [
            'timezone' => 'Time zone',
        ],
        'placeholder' => [
            'timezone' => 'Follow device time zone',
        ],
        'helper' => [
            'unverified' => 'The email address is unverified.',
        ],
        'button' => [
            'device_timezone' => 'Use device time zone',
        ],
        'status' => [
            'verification_sent' => 'A new verification link has been sent to the email address.',
            'updated' => 'Profile updated.',
        ],
    ],

    'security' => [
        'title' => 'Security settings',
        'card' => [
            'title' => 'Update password',
            'description' => 'Use a long, random password to protect the account.',
        ],
        'status' => [
            'password_updated' => 'Password updated.',
        ],
    ],

    'avatar' => [
        'title' => 'Profile photo',
        'choose' => 'Choose photo',
        'remove' => 'Remove photo',
        'save' => 'Save photo',
        'discard' => 'Discard upload',
        'retry' => 'Retry upload',
        'uploading' => 'Uploading temporary photo…',
        'processing' => 'Processing photo…',
        'removing' => 'The profile photo will be removed after saving.',
        'saved' => 'Profile photo saved.',
        'too_large' => 'The file must not exceed 2 MB.',
        'failed' => 'The photo could not be processed. Try uploading another image.',
        'expired' => 'The upload has expired. Upload the photo again.',
    ],

    'disable_account' => [
        'card' => [
            'title' => 'Disable account',
            'description' => 'Disable account access while retaining associated resources and data.',
        ],
        'helper' => [
            'warning' => 'Warning',
            'access_disabled' => 'Account access will be disabled.',
        ],
        'button' => [
            'disable' => 'Disable account',
        ],
        'dialog' => [
            'title' => 'Confirm account deactivation',
            'description' => 'The account will be disabled and signed out. Associated resources and data will be retained. Enter the password to confirm.',
        ],
    ],

    'two_factor' => [
        'card' => [
            'title' => 'Two-factor authentication',
            'description' => 'Manage two-factor authentication settings.',
        ],
        'helper' => [
            'disabled' => 'Enabling 2FA requires a secure code from a TOTP-supported app during login.',
            'enabled' => 'Login requires a secure code from a TOTP-supported app.',
        ],
        'button' => [
            'setup' => 'Continue setup',
            'enable' => 'Enable 2FA',
            'disable' => 'Disable 2FA',
            'close' => 'Close',
            'back' => 'Back',
            'confirm' => 'Confirm',
        ],
        'error' => [
            'qr_code' => 'Failed to fetch QR code.',
            'setup_key' => 'Failed to fetch setup key.',
            'recovery_codes' => 'Failed to fetch recovery codes.',
        ],
        'recovery' => [
            'card' => [
                'title' => '2FA recovery codes',
                'description' => 'Recovery codes restore account access if the 2FA device is lost. Store the codes in a secure password manager.',
            ],
            'helper' => [
                'single_use' => 'Each recovery code can be used once and is removed after use. Use Regenerate codes to create new codes.',
            ],
            'button' => [
                'regenerate' => 'Regenerate codes',
                'hide' => 'Hide recovery codes',
                'view' => 'View recovery codes',
            ],
        ],
        'setup' => [
            'enabled' => [
                'title' => 'Two-factor authentication enabled',
                'description' => '2FA is enabled. Scan the QR code or enter the setup key in the authenticator app.',
            ],
            'verify' => [
                'title' => 'Verify authentication code',
                'description' => 'Enter the 6-digit code from the authenticator app.',
            ],
            'enable' => [
                'title' => 'Enable two-factor authentication',
                'description' => 'Scan the QR code or enter the setup key in the authenticator app to finish enabling 2FA.',
            ],
        ],
    ],

    'passkey' => [
        'card' => [
            'title' => 'Passkeys',
            'description' => 'Manage passkeys for passwordless login.',
        ],
        'label' => [
            'name' => 'Passkey name',
        ],
        'placeholder' => [
            'name' => 'e.g., MacBook Pro, iPhone',
        ],
        'helper' => [
            'unsupported' => 'Passkeys are not supported in this browser.',
            'name' => 'A name makes the passkey easier to identify.',
            'device_name' => ':browser on :os',
            'added' => 'Added :time',
            'last_used' => 'Last used :time',
        ],
        'button' => [
            'login' => 'Log in with a passkey',
            'confirm' => 'Confirm with passkey',
            'add' => 'Add passkey',
            'register' => 'Register passkey',
            'remove' => 'Remove',
            'remove_passkey' => 'Remove passkey',
        ],
        'status' => [
            'verifying' => 'Verifying passkey...',
            'registering' => 'Registering passkey...',
            'removing' => 'Removing passkey...',
        ],
        'empty' => [
            'title' => 'No passkeys yet',
            'description' => 'Add a passkey to log in without a password.',
        ],
        'dialog' => [
            'title' => 'Remove passkey',
            'description' => 'Remove the passkey ":name"? This passkey will no longer be available for login.',
        ],
    ],
];

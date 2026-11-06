<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'card' => [
        'title' => 'Settings',
        'description' => 'Application-wide configuration such as identity, sign-in, and email.',
    ],

    'modules' => [
        'title' => 'Modules',
        'card' => ['title' => 'Modules', 'description' => 'Enable or disable installed application modules.'],
        'label' => ['chat' => 'Chat', 'documentation' => 'Documentation', 'backup' => 'Backup', 'api' => 'External API'],
        'helper' => [
            'chat' => 'Internal conversations and realtime messages.',
            'documentation' => 'Application documentation and guides.',
            'backup' => 'System backup and restore operations.',
            'api' => 'API access for partner systems using client tokens.',
        ],
        'status' => ['updated' => 'Module settings updated.'],
    ],

    'general' => [
        'title' => 'General settings',
        'card' => [
            'title' => 'General settings',
            'description' => 'Application identity and support contact.',
        ],
        'label' => [
            'site_name' => 'Application name',
            'site_description' => 'Application description',
            'support_email' => 'Support email',
        ],
        'helper' => [
        ],
        'status' => [
            'updated' => 'General settings updated.',
        ],
    ],

    'brand' => [
        'title' => 'Brand settings',
        'card' => [
            'title' => 'Brand settings',
            'description' => 'Manage the application logo, favicon, authentication background, and color.',
        ],
        'label' => [
            'logo_type' => 'Logo type',
            'logo' => 'Full logo',
            'logo_text' => 'Symbol + name',
            'favicon' => 'Favicon',
            'auth_background' => 'Split authentication background',
            'color' => 'Primary color',
        ],
        'helper' => [
            'logo' => 'Image already includes the name. No text is added.',
            'logo_text' => 'Symbol image. The application name is shown beside it.',
            'auth_background' => 'Shown on the split authentication layout. Use a portrait image, up to 5 MB. The default illustration is used when no custom image is set.',
            'color' => 'Choose a preset for buttons and navigation accents.',
        ],
        'button' => [
            'choose_logo_text' => 'Choose symbol',
            'remove_logo_text' => 'Remove symbol',
            'remove_logo' => 'Remove logo',
            'remove_favicon' => 'Remove favicon',
            'choose_logo' => 'Choose logo',
            'choose_auth_background' => 'Choose background',
            'remove_auth_background' => 'Restore default illustration',
            'choose_favicon' => 'Choose favicon',
        ],
        'status' => [
            'updated' => 'Brand settings updated.',
            'default' => 'Default',
            'current' => 'Current image',
            'selected' => 'New image selected',
            'removing' => 'Will be removed',
            'uploading' => 'Uploading brand images',
        ],

        'error' => [
            'store_failed' => 'The brand image could not be stored. Try again.',
        ],
    ],

    'auth' => [
        'title' => 'Authentication settings',
        'card' => [
            'title' => 'Authentication settings',
            'description' => 'Registration, email verification, password, and login limits.',
        ],
        'label' => [
            'registration_enabled' => 'Allow registration',
            'email_verification_required' => 'Require email verification',
            'password_min_length' => 'Minimum password length',
            'login_max_attempts' => 'Maximum login attempts',
            'login_lockout_minutes' => 'Login lockout (minutes)',
        ],
        'helper' => [
            'registration_enabled' => 'Close the registration page and new account submissions when disabled.',
            'email_verification_required' => 'Unverified accounts must verify their email before accessing protected pages.',
        ],
        'status' => [
            'updated' => 'Authentication settings updated.',
        ],
    ],

    'layout' => [
        'title' => 'Layout settings',
        'card' => [
            'title' => 'Layout settings',
            'description' => 'Choose the application and authentication page layouts.',
        ],
        'label' => [
            'app_layout' => 'Application layout',
            'auth_layout' => 'Authentication layout',
        ],
        'status' => [
            'updated' => 'Layout settings updated.',
        ],
    ],

    'mail' => [
        'title' => 'Mail settings',
        'card' => [
            'title' => 'Mail settings',
            'description' => 'Configure email delivery and the shared email layout.',
        ],
        'section' => [
            'delivery' => 'Delivery',
            'sender' => 'Sender',
            'layout' => 'Email layout',
            'preview' => 'Preview',
        ],
        'label' => [
            'mailer' => 'Delivery mode',
            'smtp_host' => 'SMTP host',
            'smtp_port' => 'SMTP port',
            'smtp_scheme' => 'Connection security',
            'smtp_username' => 'SMTP username',
            'smtp_password' => 'SMTP password',
            'from_address' => 'From address',
            'from_name' => 'From name',
            'show_logo' => 'Show brand logo',
            'footer_text' => 'Footer text',
        ],
        'option' => [
            'environment' => 'Server default',
            'none' => 'No encryption',
            'log' => 'Do not send (record only)',
            'smtp' => 'SMTP',
            'starttls' => 'STARTTLS (usually port 587)',
            'smtps' => 'TLS (usually port 465)',
        ],
        'hint' => [
            'environment' => 'Uses the mail configuration set on the server.',
            'mailpit' => 'Local Mailpit uses host lerd-mailpit, port 1025, no encryption, and empty credentials.',
            'log' => 'Emails are not sent. They are recorded in the application log for the operator.',
            'smtp_password' => 'Leave blank to keep the saved password.',
            'brand' => 'Logo and button color follow Brand settings.',
        ],
        'status' => [
            'updated' => 'Mail settings updated.',
            'password_saved' => 'A password is saved.',
            'test_sent' => 'Test email processed.',
            'test_failed' => 'Unable to send the test email. Check the mail settings.',
        ],
        'preview' => [
            'subject' => 'Email preview',
            'greeting' => 'Hello!',
            'body' => 'This is a preview of the email layout. No email was sent.',
            'action' => 'Sample button',
        ],
        'test' => [
            'title' => 'Send a test email',
            'email' => 'Recipient email',
            'hint' => 'Save changes before sending a test email.',
            'send' => 'Send test email',
            'subject' => 'Mail settings test',
            'heading' => 'Mail settings test',
            'body' => 'This email confirms the selected delivery mode and layout are working.',
        ],
    ],
];

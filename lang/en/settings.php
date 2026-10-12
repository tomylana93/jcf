<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'card' => [
        'title' => 'Settings',
        'description' => 'Manage application settings.',
    ],

    'general' => [
        'title' => 'General settings',
        'card' => [
            'title' => 'General settings',
            'description' => 'Application identity and support contact.',
        ],
        'label' => [
            'public_pages_enabled' => 'Enable public pages',
            'site_name' => 'Application name',
            'site_description' => 'Application description',
            'support_email' => 'Support email',
        ],
        'helper' => [
            'public_pages_enabled' => 'Show a public home page at the root URL. When disabled, the root URL redirects to login or the dashboard.',
        ],
        'status' => [
            'updated' => 'General settings updated.',
        ],
    ],

    'brand' => [
        'title' => 'Brand Settings',
        'card' => [
            'title' => 'Brand Settings',
            'description' => 'Manage the application logo, favicon, authentication background, and color.',
        ],
        'label' => [
            'logo_type' => 'Logo type',
            'logo' => 'Logo only',
            'logo_text' => 'Logo + text',
            'favicon' => 'Favicon',
            'auth_background' => 'Split authentication background',
            'color' => 'Primary color',
        ],
        'hint' => [
            'logo' => 'Image containing both the logo and text. No additional text is displayed.',
            'logo_text' => 'Symbol image without text. The application name is added beside it.',
            'auth_background' => 'Shown on the split authentication layout. Use a portrait image, up to 5 MB. The default illustration is used when no custom image is set.',
            'color' => 'Choose a preset for buttons and navigation accents.',
        ],
        'button' => [
            'choose_logo_text' => 'Choose logo + text',
            'remove_logo_text' => 'Remove logo + text',
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
        'title' => 'Mail Settings',
        'card' => [
            'title' => 'Mail Settings',
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
            'log' => 'Log only',
            'smtp' => 'SMTP',
            'starttls' => 'STARTTLS (usually port 587)',
            'smtps' => 'TLS (usually port 465)',
        ],
        'hint' => [
            'log' => 'Messages are recorded in the application log and are not delivered.',
            'smtp_password' => 'Leave blank to keep the saved password.',
            'brand' => 'Logo and button color follow Brand Settings.',
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
            'send' => 'Send test email',
            'subject' => 'Mail settings test',
            'heading' => 'Mail settings test',
            'body' => 'This email confirms the selected delivery mode and layout are working.',
        ],
    ],
];

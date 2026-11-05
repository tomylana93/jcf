export type GeneralSettings = {
    site_name: string;
    site_description: string;
    support_email: string | null;
};

/** Mirrors App\Enums\LogoType; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type LogoType = 'logo_only' | 'logo_text';

/** Mirrors App\Enums\AppLayout; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type AppLayoutVariant = 'sidebar' | 'header';

/** Mirrors App\Enums\AuthLayout; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type AuthLayoutVariant = 'split' | 'card' | 'simple';

/** Mirrors App\Enums\Mailer; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type Mailer = 'environment' | 'log' | 'smtp';

export type LogoTypeOption = { value: LogoType; label: string; name: string };

export type BrandSettings = {
    logo_type: LogoType;
    logo_text_url: string | null;
    logo_url: string | null;
    favicon_url: string | null;
    auth_background_url: string | null;
    color: string;
};

export type ColorPreset = {
    value: string;
    label: string;
    name: string;
    swatch: string;
};

export type AuthSettings = {
    registration_enabled: boolean;
    email_verification_required: boolean;
    password_min_length: number;
    login_max_attempts: number;
    login_lockout_minutes: number;
};

export type LayoutSettings = {
    app_layout: AppLayoutVariant;
    auth_layout: AuthLayoutVariant;
};

export type MailSettings = {
    mailer: Mailer;
    smtp_host: string;
    smtp_port: number;
    smtp_scheme: 'none' | 'smtp' | 'smtps';
    smtp_username: string;
    from_address: string;
    from_name: string;
    show_logo: boolean;
    footer_text: string;
    has_smtp_password: boolean;
};

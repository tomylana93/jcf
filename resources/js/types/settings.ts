export type GeneralSettings = {
    site_name: string;
    site_description: string;
    support_email: string | null;
    public_pages_enabled: boolean;
};

export type LogoType = 'logo_only' | 'logo_text';

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
    app_layout: 'sidebar' | 'header';
    auth_layout: 'split' | 'card' | 'simple';
};

export type MailSettings = {
    mailer: 'environment' | 'log' | 'smtp';
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

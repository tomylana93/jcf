import type { EditorDocument } from './editor';
import type { LogoType } from './settings';
export type DocumentationForm = {
    title: string;
    slug: string;
    summary: string;
    content_json: EditorDocument;
    status: 'draft' | 'published';
};

export type LoginForm = {
    email: string;
    password: string;
};

export type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export type ForgotPasswordForm = {
    email: string;
};

export type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export type ConfirmPasswordForm = {
    password: string;
};

export type TwoFactorCodeForm = {
    code: string;
};

export type TwoFactorRecoveryForm = {
    recovery_code: string;
};

export type UpdateProfileForm = {
    name: string;
    email: string;
    timezone: string;
};

export type UploadAvatarForm = {
    avatar: File | null;
};

export type SaveAvatarForm = {
    temporary_avatar: string | null;
    remove_avatar: boolean;
};

export type UpdatePasswordForm = {
    current_password: string;
    password: string;
    password_confirmation: string;
};

export type DisableAccountForm = {
    password: string;
};

export type RegisterPasskeyForm = {
    name: string;
};

export type UpdateGeneralSettingsForm = {
    site_name: string;
    site_description: string;
    support_email: string;
    public_pages_enabled: boolean;
};

export type UpdateBrandSettingsForm = {
    logo_type: LogoType;
    logo_text: File | null;
    remove_logo_text: boolean;
    _method: 'patch';
    color: string;
    logo: File | null;
    favicon: File | null;
    auth_background: File | null;
    remove_logo: boolean;
    remove_favicon: boolean;
    remove_auth_background: boolean;
};

export type UpdateAuthSettingsForm = {
    registration_enabled: boolean;
    email_verification_required: boolean;
    password_min_length: number;
    login_max_attempts: number;
    login_lockout_minutes: number;
};

export type UpdateLayoutSettingsForm = {
    app_layout: 'sidebar' | 'header';
    auth_layout: 'split' | 'card' | 'simple';
};

export type UpdateMailSettingsForm = {
    mailer: 'log' | 'smtp';
    smtp_host: string;
    smtp_port: number;
    smtp_scheme: 'smtp' | 'smtps';
    smtp_username: string;
    smtp_password: string;
    from_address: string;
    from_name: string;
    show_logo: boolean;
    footer_text: string;
};

export type SendMailSettingsTestForm = {
    test_email: string;
};

export type UpdateUserStatusForm = { status: 'active' | 'disabled' };

export type UserForm = {
    name: string;
    email: string;
    role: string;
    status: 'active' | 'disabled';
};

export type UpdateUserPermissionsForm = { permissions: string[] };

export type UploadBackupForm = { archive: File | null };
export type RestoreBackupForm = { confirmation: boolean };

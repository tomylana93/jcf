<?php

declare(strict_types=1);

return [
    'logo_type' => [
        'LogoOnly' => 'Logo only',
        'LogoText' => 'Logo + text',
    ],

    'brand_color' => [
        'Neutral' => 'Netral',
        'Blue' => 'Biru',
        'Green' => 'Hijau',
        'Purple' => 'Ungu',
        'Rose' => 'Rose',
        'Orange' => 'Oranye',
    ],

    'app_layout' => [
        'Sidebar' => 'Sidebar',
        'Header' => 'Header',
    ],

    'auth_layout' => [
        'Split' => 'Split screen',
        'Card' => 'Card',
        'Simple' => 'Simple',
    ],

    'user_status' => [
        'Active' => 'Aktif',
        'Disabled' => 'Dinonaktifkan',
    ],

    'role' => [
        'SuperAdmin' => 'Super Admin',
    ],

    'permission' => [
        'UseChat' => 'Gunakan chat',
        'ViewDocumentation' => 'Lihat dokumentasi',
        'ManageDocumentation' => 'Kelola dokumentasi',
        'ViewSystem' => 'Lihat kesehatan sistem',
        'ManageBackups' => 'Kelola backup',
        'ViewAuditLogs' => 'Lihat audit log',
        'ViewSettings' => 'Lihat pengaturan',
        'ManageSettings' => 'Kelola pengaturan',
        'ViewUsers' => 'Lihat pengguna',
        'ManageUsers' => 'Kelola pengguna',
        'AssignUserPermissions' => 'Tetapkan permission pengguna',
        'ViewApiClients' => 'Lihat klien API',
        'ManageApiClients' => 'Kelola klien API',
    ],

    'api_client_status' => [
        'Active' => 'Aktif',
        'Disabled' => 'Dinonaktifkan',
    ],

    'api_token_ability' => [
        'Read' => 'Baca',
        'Write' => 'Tulis',
    ],

    'api_token_expiration' => [
        'ThirtyDays' => '30 hari',
        'NinetyDays' => '90 hari',
        'OneYear' => '1 tahun',
    ],

    'locale' => [
        'English' => 'Bahasa Inggris',
        'Indonesian' => 'Bahasa Indonesia',
    ],

    'audit_event' => [
        'UserCreated' => 'Pengguna dibuat',
        'UserUpdated' => 'Pengguna diperbarui',
        'UserStatusUpdated' => 'Status akun diubah',
        'UserPermissionsUpdated' => 'Permission diubah',
        'UserInvitationQueued' => 'Undangan masuk antrean',
        'SettingsUpdated' => 'Pengaturan diperbarui',
        'ApiClientCreated' => 'Klien API dibuat',
        'ApiClientUpdated' => 'Klien API diperbarui',
        'ApiClientTokenIssued' => 'Token klien API diterbitkan',
        'ApiClientTokenRevoked' => 'Token klien API dicabut',
    ],
];

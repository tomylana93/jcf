<?php

declare(strict_types=1);

use App\Enums\ApiClientStatus;
use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use App\Enums\DocumentationStatus;
use App\Enums\HealthDetail;
use App\Enums\LogoType;
use App\Enums\Mailer;
use App\Enums\Module;
use App\Enums\NotificationFilter;
use App\Enums\OperationStatus;
use App\Enums\Permission;
use App\Enums\UserStatus;

/**
 * @return list<string>
 */
function frontendUnionMembers(string $file, string $type): array
{
    $source = (string) file_get_contents(resource_path('js/types/'.$file));
    preg_match('/export type '.$type.'\s*=([^;]+);/', $source, $union);
    preg_match_all("/'([^']+)'/", $union[1] ?? '', $members);

    return $members[1];
}

describe('Frontend enum mirrors', function (): void {
    it('lists every backend permission in the PermissionName union', function (): void {
        expect(frontendUnionMembers('permission.ts', 'PermissionName'))
            ->toEqualCanonicalizing(array_column(Permission::cases(), 'value'));
    });

    it('lists every backend module in the ModuleName union', function (): void {
        expect(frontendUnionMembers('features.ts', 'ModuleName'))
            ->toEqualCanonicalizing(array_column(Module::cases(), 'value'));
    });

    it('lists every backend case in the mirrored frontend union', function (string $enum, string $file, string $type): void {
        expect(frontendUnionMembers($file, $type))
            ->toEqualCanonicalizing(array_column($enum::cases(), 'value'));
    })->with([
        'user status' => [UserStatus::class, 'user.ts', 'UserStatus'],
        'api client status' => [ApiClientStatus::class, 'api-tokens.ts', 'ApiClientStatus'],
        'documentation status' => [DocumentationStatus::class, 'documentation.ts', 'DocumentationStatus'],
        'operation status' => [OperationStatus::class, 'backup.ts', 'OperationStatus'],
        'health detail' => [HealthDetail::class, 'system.ts', 'HealthDetail'],
        'notification filter' => [NotificationFilter::class, 'notification.ts', 'NotificationFilter'],
        'logo type' => [LogoType::class, 'settings.ts', 'LogoType'],
        'app layout' => [AppLayout::class, 'settings.ts', 'AppLayoutVariant'],
        'auth layout' => [AuthLayout::class, 'settings.ts', 'AuthLayoutVariant'],
        'mailer' => [Mailer::class, 'settings.ts', 'Mailer'],
    ]);
});

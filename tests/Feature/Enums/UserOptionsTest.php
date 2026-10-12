<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\UserStatus;

describe('user enum options', function (): void {
    it('provides the superadmin option with its label and color', function (string $locale): void {
        app()->setLocale($locale);

        expect(Role::options())->toBe([
            ['value' => 'superadmin', 'label' => 'Super Admin', 'name' => 'SuperAdmin', 'color' => 'red'],
        ]);
    })->with(['en', 'id']);

    it('distinguishes active and disabled status badges', function (UserStatus $status, string $variant): void {
        expect($status->badgeVariant())->toBe($variant);
    })->with([
        'active' => [UserStatus::Active, 'default'],
        'disabled' => [UserStatus::Disabled, 'secondary'],
    ]);
});

<?php

declare(strict_types=1);

use App\Enums\UserStatus;

describe('User status badges', function (): void {
    test('maps each status to its badge variant', function (UserStatus $status, string $variant): void {
        expect($status->badgeVariant())->toBe($variant);
    })->with([
        'active' => [UserStatus::Active, 'default'],
        'disabled' => [UserStatus::Disabled, 'secondary'],
    ]);
});

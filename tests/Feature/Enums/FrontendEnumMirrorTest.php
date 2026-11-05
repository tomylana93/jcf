<?php

declare(strict_types=1);

use App\Enums\Module;
use App\Enums\Permission;

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
});

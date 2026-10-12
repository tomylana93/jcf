<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Role as RoleModel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

#[Signature('init:permission')]
#[Description('Initialize enum-defined roles and permissions without changing direct user permissions')]
final class InitializePermissions extends Command
{
    public function handle(PermissionRegistrar $registrar): int
    {
        $registrar->forgetCachedPermissions();

        DB::transaction(function (): void {
            foreach (Role::cases() as $role) {
                $model = RoleModel::query()->firstOrCreate([
                    'name' => $role->value,
                    'guard_name' => 'web',
                ]);

                $model->syncPermissions($role->permissions());
            }

        });

        $registrar->forgetCachedPermissions();
        $this->info('Roles and permissions initialized.');

        return self::SUCCESS;
    }
}

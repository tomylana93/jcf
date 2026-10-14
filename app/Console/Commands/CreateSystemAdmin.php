<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('init:superadmin')]
#[Description('Initialize the SuperAdmin account using configuration')]
final class CreateSystemAdmin extends Command
{
    public function handle(): int
    {
        $validator = Validator::make([
            'name' => config('jalara.superadmin.name'),
            'email' => config('jalara.superadmin.email'),
            'password' => config('jalara.superadmin.password'),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();
        $email = Str::lower($data['email']);

        $user = DB::transaction(function () use ($data, $email): ?User {
            $user = User::query()->where('email', $email)->lockForUpdate()->first();

            if ($user !== null) {
                return $user->hasRole(Role::SuperAdmin) ? $user : null;
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => $data['password'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles(Role::SuperAdmin);

            return $user;
        });

        if ($user === null) {
            $this->error('The configured email belongs to an existing user without the SuperAdmin role.');

            return self::FAILURE;
        }

        $this->info($user->wasRecentlyCreated
            ? 'System administrator created.'
            : 'System administrator already exists.');

        return self::SUCCESS;
    }
}

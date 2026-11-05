<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProfile
{
    public function __construct(private PrepareEmailChange $prepareEmailChange) {}

    /**
     * @param  array{name: string, email: string, timezone?: string|null}  $data
     */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->fill($data);

            $this->prepareEmailChange->handle($user);

            $user->save();
        });
    }
}

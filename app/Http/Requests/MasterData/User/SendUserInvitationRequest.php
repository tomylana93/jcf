<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class SendUserInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('sendInvitation', $target) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}

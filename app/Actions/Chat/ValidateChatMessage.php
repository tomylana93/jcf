<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use Illuminate\Support\Facades\Validator;

final class ValidateChatMessage
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
            'client_message_id' => ['required', 'uuid'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{body: string, client_message_id: string}
     */
    public function handle(array $data): array
    {
        if (is_string($data['body'] ?? null)) {
            $data['body'] = mb_trim($data['body']);
        }

        /** @var array{body: string, client_message_id: string} $validated */
        $validated = Validator::make($data, self::rules())->validate();

        return [
            'body' => $validated['body'],
            'client_message_id' => mb_strtolower($validated['client_message_id']),
        ];
    }
}

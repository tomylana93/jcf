<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData\ApiClient;

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveApiClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('apiClient');

        return $client instanceof ApiClient
            ? $this->user()->can('update', $client)
            : $this->user()->can('create', ApiClient::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::enum(ApiClientStatus::class)],
        ];
    }

    /** @return array{name: string, description: string|null, status: string} */
    public function apiClientData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
            'status' => $this->string('status')->toString(),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => __('validation.attributes.client_name')];
    }
}

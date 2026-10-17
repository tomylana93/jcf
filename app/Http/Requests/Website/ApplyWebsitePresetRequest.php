<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use App\Enums\WebsitePreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApplyWebsitePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['preset' => ['required', Rule::enum(WebsitePreset::class)]];
    }
}

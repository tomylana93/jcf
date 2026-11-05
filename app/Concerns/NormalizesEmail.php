<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Lowercases the submitted email before validation so uniqueness and login lookups match.
 */
trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }
}

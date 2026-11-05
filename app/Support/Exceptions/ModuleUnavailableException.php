<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use App\Enums\Module;
use Illuminate\Database\RecordsNotFoundException;

/**
 * Raised by application operations of a module that is unavailable or switched off; the exception handler renders it as 404.
 */
final class ModuleUnavailableException extends RecordsNotFoundException
{
    public static function for(Module $module): self
    {
        return new self('Module unavailable: '.$module->value);
    }

    public static function none(): self
    {
        return new self('No optional module is available.');
    }
}

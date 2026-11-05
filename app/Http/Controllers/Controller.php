<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /**
     * Flash a success toast for the next Inertia page.
     */
    protected function toastSuccess(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }
}

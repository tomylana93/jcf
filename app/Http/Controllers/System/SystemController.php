<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Actions\System\GetSystemHealth;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class SystemController extends Controller
{
    public function index(GetSystemHealth $getSystemHealth): Response
    {
        Gate::authorize(Permission::ViewSystem);

        return Inertia::render('system/Index', ['checks' => $getSystemHealth->handle()]);
    }
}

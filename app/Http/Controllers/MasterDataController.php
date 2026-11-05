<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The master-data menu landing page; it is open to anyone who may manage at least one of its sections.
 */
final class MasterDataController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(Gate::any([Permission::ViewUsers->value, Permission::ManageBackups->value, Permission::ViewApiClients->value]), 403);

        return Inertia::render('master-data/Index');
    }
}

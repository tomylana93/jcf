<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\IndexAuditLogRequest;
use App\Http\Resources\Audit\AuditLogResource;
use App\Models\AuditLog;
use App\Tables\Audit\AuditLogTable;
use Inertia\Inertia;
use Inertia\Response;

final class AuditLogController extends Controller
{
    public function index(IndexAuditLogRequest $request, AuditLogTable $table): Response
    {
        return Inertia::render('audit/Index', [
            'logs' => $table->paginate($request)->through(fn (AuditLog $log): array => new AuditLogResource($log)->resolve($request)),
        ]);
    }
}

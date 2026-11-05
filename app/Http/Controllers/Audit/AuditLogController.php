<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\IndexAuditLogRequest;
use App\Http\Resources\Audit\AuditLogResource;
use App\Tables\Audit\AuditLogTable;
use Inertia\Inertia;
use Inertia\Response;

final class AuditLogController extends Controller
{
    public function index(IndexAuditLogRequest $request, AuditLogTable $table): Response
    {
        return Inertia::render('audit/Index', [
            'logs' => $table->paginateResource($request, AuditLogResource::class),
            'eventOptions' => AuditEvent::options(),
        ]);
    }
}

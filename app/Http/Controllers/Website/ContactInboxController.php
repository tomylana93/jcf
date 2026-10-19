<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\SetContactMessageHandled;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Website\ContactMessageResource;
use App\Models\ContactMessage;
use App\Tables\Website\ContactMessageTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ContactInboxController extends Controller
{
    public function index(Request $request, ContactMessageTable $table): Response
    {
        Gate::authorize(Permission::ViewContactInbox->value);

        return Inertia::render('website/inbox/Index', ['messages' => ContactMessageResource::collection($table->paginate($request))->response()->getData(true)]);
    }

    public function show(ContactMessage $message): Response
    {
        Gate::authorize(Permission::ViewContactInbox->value);

        return Inertia::render('website/inbox/Show', ['message' => [
            'id' => $message->id, 'name' => $message->name, 'email' => $message->email, 'subject' => $message->subject, 'message' => $message->message,
            'created_at' => $message->created_at?->toIso8601String(), 'handled_at' => $message->handled_at?->toIso8601String(),
        ]]);
    }

    public function update(Request $request, ContactMessage $message, SetContactMessageHandled $update): RedirectResponse
    {
        Gate::authorize(Permission::ViewContactInbox->value);
        $data = $request->validate(['handled' => ['required', 'boolean']]);
        $update->handle($request->user(), $message, (bool) $data['handled']);

        return to_route('website.inbox.show', ['message' => $message->id]);
    }
}

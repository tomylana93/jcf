<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\GetChatHistory;
use App\Actions\Chat\GetChatRecipients;
use App\Actions\Chat\MarkChatConversationRead;
use App\Actions\Chat\QueryChatConversations;
use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatHistoryRequest;
use App\Http\Requests\Chat\ChatQueryRequest;
use App\Http\Requests\Chat\MarkChatConversationReadRequest;
use App\Http\Requests\Chat\SendChatMessageRequest;
use App\Http\Requests\Chat\StartDirectConversationRequest;
use App\Http\Resources\Chat\ChatConversationResource;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Http\Resources\Chat\ChatUserResource;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ChatController extends Controller
{
    public function index(ChatQueryRequest $request): Response
    {
        return Inertia::render('chat/Index');
    }

    public function inbox(ChatQueryRequest $request, QueryChatConversations $query): JsonResponse
    {
        $page = $query->handle($request->user())->latest('updated_at')->orderByDesc('id')->cursorPaginate(20);

        return response()->json([
            'data' => ChatConversationResource::collection($page->items())->resolve($request),
            'next_cursor' => $page->nextCursor()?->encode(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function recipients(ChatQueryRequest $request, GetChatRecipients $getRecipients): JsonResponse
    {
        $page = $getRecipients->handle($request->user(), $request->string('search')->toString());

        return response()->json([
            'data' => ChatUserResource::collection($page->items())->resolve($request),
            'next_cursor' => $page->nextCursor()?->encode(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(StartDirectConversationRequest $request, StartDirectConversation $start, QueryChatConversations $query): JsonResponse
    {
        $conversation = $start->handle($request->user(), User::query()->findOrFail($request->validated()['recipient_id']));

        return response()->json(['data' => new ChatConversationResource($query->handle($request->user())->findOrFail($conversation->id))])
            ->header('Cache-Control', 'no-store, private');
    }

    public function history(ChatHistoryRequest $request, ChatConversation $conversation, GetChatHistory $getHistory): JsonResponse
    {
        $result = $getHistory->handle($request->user(), $conversation,
            $request->filled('before') ? $request->integer('before') : null,
            $request->filled('after') ? $request->integer('after') : null);

        return response()->json([
            'data' => ChatMessageResource::collection($result['messages'])->resolve($request),
            'has_more' => $result['has_more'],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function send(SendChatMessageRequest $request, ChatConversation $conversation, SendChatMessage $send): JsonResponse
    {
        $message = $send->handle($request->user(), $conversation, $request->validated());

        return response()->json(['data' => new ChatMessageResource($message)])->header('Cache-Control', 'no-store, private');
    }

    public function read(MarkChatConversationReadRequest $request, ChatConversation $conversation, MarkChatConversationRead $markRead): JsonResponse
    {
        $participant = $markRead->handle($request->user(), $conversation, (int) $request->validated()['sequence']);

        return response()->json(['last_read_sequence' => $participant->last_read_sequence])->header('Cache-Control', 'no-store, private');
    }
}

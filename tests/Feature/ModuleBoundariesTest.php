<?php

declare(strict_types=1);

use App\Events\ChatMessageCreated;
use App\Models\ApiClient;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Documentation;
use App\Policies\ChatConversationPolicy;
use App\Policies\DocumentationPolicy;
use Database\Factories\ChatConversationFactory;
use Database\Factories\ChatMessageFactory;
use Database\Factories\ChatParticipantFactory;
use Database\Factories\DocumentationFactory;

arch('keeps search and the application shell independent of optional internals')
    ->expect(['App\Actions\Search', 'App\Http\Middleware'])
    ->not->toUse([
        'App\Actions\Chat',
        'App\Actions\Documentation',
        Documentation::class,
        ChatConversation::class,
        ChatParticipant::class,
        ChatMessage::class,
    ]);

arch('keeps documentation persistence within its owning domain')
    ->expect(Documentation::class)
    ->toOnlyBeUsedIn([
        'App\Actions\Documentation',
        'App\Http\Controllers\Documentation',
        'App\Http\Requests\Documentation',
        DocumentationPolicy::class,
        DocumentationFactory::class,
    ]);

arch('keeps chat persistence within its owning domain')
    ->expect([ChatConversation::class, ChatParticipant::class, ChatMessage::class])
    ->toOnlyBeUsedIn([
        'App\Actions\Chat',
        ChatConversation::class,
        ChatParticipant::class,
        ChatMessage::class,
        ChatMessageCreated::class,
        'App\Http\Controllers\Chat',
        'App\Http\Requests\Chat',
        'App\Http\Resources\Chat',
        ChatConversationPolicy::class,
        ChatConversationFactory::class,
        ChatParticipantFactory::class,
        ChatMessageFactory::class,
    ]);

arch('prevents direct coupling between optional domain actions')
    ->expect('App\Actions\Documentation')
    ->not->toUse(['App\Actions\Chat', 'App\Actions\Operations\Backup', ApiClient::class]);

arch('prevents direct coupling from chat to other optional domains')
    ->expect('App\Actions\Chat')
    ->not->toUse(['App\Actions\Documentation', 'App\Actions\Operations\Backup', Documentation::class, ApiClient::class]);

arch('keeps identity actions independent of optional implementations')
    ->expect(['App\Actions\Identity', 'App\Actions\Account'])
    ->not->toUse(['App\Actions\Chat', 'App\Actions\Documentation', 'App\Actions\Operations']);

arch('keeps backup operations independent of other optional implementations')
    ->expect('App\Actions\Operations')
    ->not->toUse(['App\Actions\Chat', 'App\Actions\Documentation', Documentation::class, ChatMessage::class, ChatConversation::class, ChatParticipant::class]);

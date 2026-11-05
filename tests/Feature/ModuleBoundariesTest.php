<?php

declare(strict_types=1);

arch('keeps search and the application shell independent of optional internals')
    ->expect(['App\Actions\Search', 'App\Http\Middleware'])
    ->not->toUse([
        'App\Actions\Chat',
        'App\Actions\Documentation',
        'App\Models\Documentation',
        'App\Models\ChatConversation',
        'App\Models\ChatParticipant',
        'App\Models\ChatMessage',
    ]);

arch('keeps documentation persistence within its owning domain')
    ->expect('App\Models\Documentation')
    ->toOnlyBeUsedIn([
        'App\Actions\Documentation',
        'App\Http\Controllers\Documentation',
        'App\Http\Requests\Documentation',
        'App\Policies\DocumentationPolicy',
        'Database\Factories\DocumentationFactory',
    ]);

arch('keeps chat persistence within its owning domain')
    ->expect(['App\Models\ChatConversation', 'App\Models\ChatParticipant', 'App\Models\ChatMessage'])
    ->toOnlyBeUsedIn([
        'App\Actions\Chat',
        'App\Models\ChatConversation',
        'App\Models\ChatParticipant',
        'App\Models\ChatMessage',
        'App\Events\ChatMessageCreated',
        'App\Http\Controllers\Chat',
        'App\Http\Requests\Chat',
        'App\Http\Resources\Chat',
        'App\Policies\ChatConversationPolicy',
        'Database\Factories\ChatConversationFactory',
        'Database\Factories\ChatParticipantFactory',
        'Database\Factories\ChatMessageFactory',
    ]);

arch('prevents direct coupling between optional domain actions')
    ->expect('App\Actions\Documentation')
    ->not->toUse(['App\Actions\Chat', 'App\Actions\MasterData\Backup', 'App\Models\ApiClient']);

arch('prevents direct coupling from chat to other optional domains')
    ->expect('App\Actions\Chat')
    ->not->toUse(['App\Actions\Documentation', 'App\Actions\MasterData\Backup', 'App\Models\Documentation', 'App\Models\ApiClient']);

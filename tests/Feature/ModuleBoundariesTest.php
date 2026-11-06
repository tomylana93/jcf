<?php

declare(strict_types=1);

use App\Console\Commands\CreateBackup as CreateBackupCommand;
use App\Console\Commands\RestoreBackup as RestoreBackupCommand;
use App\Console\Commands\VerifyBackup as VerifyBackupCommand;
use App\Events\ChatMessageCreated;
use App\Events\ChatReadUpdated;
use App\Events\ChatTypingUpdated;
use App\Events\Concerns\BroadcastsToChatParticipants;
use App\Http\Middleware\EnsureApiTokenOwner;
use App\Jobs\Operations\CreateSystemBackup;
use App\Jobs\Operations\RestoreSystemBackup;
use App\Models\ApiClient;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Documentation;
use App\Policies\ApiClientPolicy;
use App\Policies\ChatConversationPolicy;
use App\Policies\DocumentationPolicy;
use App\Providers\BackupModuleServiceProvider;
use App\Providers\ChatServiceProvider;
use App\Providers\DocumentationServiceProvider;
use Database\Factories\ApiClientFactory;
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
        'App\Http\Resources\Documentation',
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

arch('keeps system health independent of optional implementations')
    ->expect('App\Actions\System')
    ->not->toUse(['App\Actions\Chat', 'App\Actions\Documentation', 'App\Actions\Operations', Documentation::class, ChatConversation::class, ChatParticipant::class, ChatMessage::class]);

arch('keeps api client persistence within identity and token transport')
    ->expect(ApiClient::class)
    ->toOnlyBeUsedIn([
        ApiClient::class,
        'App\Actions\Identity\ApiClient',
        'App\Actions\Account',
        'App\Http\Controllers\Identity',
        'App\Http\Requests\Identity\ApiClient',
        'App\Http\Resources\Identity',
        'App\Http\Resources\Api',
        'App\Tables\Identity',
        EnsureApiTokenOwner::class,
        ApiClientPolicy::class,
        ApiClientFactory::class,
    ]);

arch('exposes backup operations only to their transport, jobs, commands and provider')
    ->expect('App\Actions\Operations')
    ->toOnlyBeUsedIn([
        'App\Actions\Operations',
        'App\Http\Controllers\Operations',
        'App\Http\Requests\Operations',
        CreateSystemBackup::class,
        RestoreSystemBackup::class,
        CreateBackupCommand::class,
        RestoreBackupCommand::class,
        VerifyBackupCommand::class,
        BackupModuleServiceProvider::class,
    ]);

arch('exposes chat actions only to chat transport, events and provider')
    ->expect('App\Actions\Chat')
    ->toOnlyBeUsedIn([
        'App\Actions\Chat',
        'App\Http\Controllers\Chat',
        'App\Http\Requests\Chat',
        ChatMessageCreated::class,
        ChatReadUpdated::class,
        ChatTypingUpdated::class,
        BroadcastsToChatParticipants::class,
        ChatServiceProvider::class,
    ]);

arch('exposes documentation actions only to documentation transport and provider')
    ->expect('App\Actions\Documentation')
    ->toOnlyBeUsedIn([
        'App\Actions\Documentation',
        'App\Http\Controllers\Documentation',
        DocumentationServiceProvider::class,
    ]);

arch('keeps application actions final, readonly and dependency-injected')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

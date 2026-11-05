<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

/**
 * Every event the audit log records; the single source for labels and filter options.
 */
enum AuditEvent: string
{
    use HasLabel, HasOptions;

    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserStatusUpdated = 'user.status_updated';
    case UserPermissionsUpdated = 'user.permissions_updated';
    case UserInvitationQueued = 'user.invitation_queued';
    case SettingsUpdated = 'settings.updated';
    case ApiClientCreated = 'api_client.created';
    case ApiClientUpdated = 'api_client.updated';
    case ApiClientTokenIssued = 'api_client.token_issued';
    case ApiClientTokenRevoked = 'api_client.token_revoked';
}

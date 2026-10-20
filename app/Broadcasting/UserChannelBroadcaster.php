<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Adds the authenticated user identifier to private user channel
 * subscriptions. Reverb records it as channel data so connected sockets can
 * be terminated when the account is disabled.
 */
final class UserChannelBroadcaster extends PusherBroadcaster
{
    /**
     * @param  Request  $request
     * @param  mixed  $result
     * @return array<string, mixed>
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (str_starts_with((string) $request->channel_name, 'private-App.Models.User.')) {
            $user = $request->user();

            throw_if($user === null, AccessDeniedHttpException::class);

            $channelData = json_encode(
                ['user_id' => (string) $user->getAuthIdentifier()],
                JSON_THROW_ON_ERROR,
            );

            return $this->decodePusherResponse(
                $request,
                $this->pusher->authorizeChannel(
                    (string) $request->channel_name,
                    (string) $request->socket_id,
                    $channelData,
                ),
            );
        }

        return parent::validAuthenticationResponse($request, $result);
    }
}

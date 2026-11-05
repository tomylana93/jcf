<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{two_factor: true, challenge: string, expires_in: int} $resource
 */
final class TwoFactorChallengeResource extends JsonResource
{
    /** @return array{two_factor: true, challenge: string, expires_in: int} */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}

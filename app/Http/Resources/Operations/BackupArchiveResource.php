<?php

declare(strict_types=1);

namespace App\Http\Resources\Operations;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a stored backup archive described by GetBackups.
 *
 * @property array{filename: string, size: int, created_at: CarbonImmutable} $resource
 */
final class BackupArchiveResource extends JsonResource
{
    /** @return array{filename: string, size: int, created_at: string} */
    public function toArray(Request $request): array
    {
        return [
            'filename' => $this->resource['filename'],
            'size' => $this->resource['size'],
            'created_at' => $this->resource['created_at']->toIso8601String(),
        ];
    }
}

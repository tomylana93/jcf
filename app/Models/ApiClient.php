<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApiClientStatus;
use Database\Factories\ApiClientFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;

/**
 * A partner system that calls the external API with its own tokens instead of a user account.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property ApiClientStatus $status
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'status'])]
final class ApiClient extends Model implements AuthenticatableContract, HasApiTokensContract
{
    /** @use HasFactory<ApiClientFactory> */
    use Authenticatable, HasApiTokens, HasFactory, HasUuids;

    public function isActive(): bool
    {
        return $this->status === ApiClientStatus::Active;
    }

    protected static function booted(): void
    {
        self::deleted(function (ApiClient $client): void {
            $client->tokens()->delete();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ApiClientStatus::class,
        ];
    }
}

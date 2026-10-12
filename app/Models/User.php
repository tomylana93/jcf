<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasSingleRole;
use App\Enums\UserStatus;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read Collection<int, Role> $roles
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $timezone
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property CarbonImmutable|null $invited_at
 * @property CarbonImmutable|null $invitation_accepted_at
 * @property UserStatus $status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'timezone'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'media'])]
final class User extends Authenticatable implements HasMedia, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasSingleRole, HasUuids, InteractsWithMedia, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->useDisk('public')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('avatar_pending')->useDisk('local')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('avatar_prepared')->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->nonOptimized()->nonQueued()
            ->performOnCollections('avatar', 'avatar_pending', 'avatar_prepared')
            ->fit(Fit::Crop, 128, 128)->format('webp');

        $this->addMediaConversion('preview')->nonOptimized()->nonQueued()
            ->performOnCollections('avatar', 'avatar_pending', 'avatar_prepared')
            ->fit(Fit::Crop, 512, 512)->format('webp');
    }

    /** @return Attribute<string, never> */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => $this->getFirstMediaUrl('avatar', 'thumb'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'immutable_datetime',
            'invitation_accepted_at' => 'immutable_datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}

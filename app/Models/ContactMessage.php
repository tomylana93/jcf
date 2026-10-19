<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $subject
 * @property string $message
 * @property CarbonImmutable|null $handled_at
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['name', 'email', 'subject', 'message'])]
#[Hidden(['name', 'email', 'message'])]
final class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    use HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['handled_at' => 'immutable_datetime'];
    }
}

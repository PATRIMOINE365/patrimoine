<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents one secure password-setup invitation for an application User.
 *
 * The database stores only the hash of the secret presented to the user.
 */
class UserInvitation extends Model
{
    use BelongsToOrganisation;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'accepted_at',
        'invalidated_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    /**
     * User who owns this invitation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether this invitation may still be consumed.
     */
    public function isUsable(): bool
    {
        return $this->accepted_at === null
            && $this->invalidated_at === null
            && $this->expires_at->isFuture();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One pending multi-factor sign-in challenge.
 *
 * After a correct email + password, Patrimoine emails the user a
 * six-digit code and hands the browser an opaque challenge token. Only
 * the pair (challenge token + correct code) produces an API token.
 *
 * The code itself is stored hashed, expires quickly, allows a small
 * number of attempts and is single-use. Challenges are rows rather than
 * cache entries so failed and abandoned sign-ins remain auditable.
 */
class MfaChallenge extends Model
{
    /**
     * Codes remain valid for this many minutes after issue.
     */
    public const CODE_LIFETIME_MINUTES = 10;

    /**
     * A challenge dies after this many wrong codes.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'code_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    /**
     * Convert stored values to appropriate PHP representations.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * The user this challenge authenticates.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this challenge can still be answered.
     */
    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && $this->attempts < self::MAX_ATTEMPTS
            && $this->expires_at->gte(Carbon::now());
    }
}

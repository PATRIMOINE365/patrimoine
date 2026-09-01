<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One pending change of a user's sign-in email address.
 *
 * The flow has three steps and this row carries it between them:
 *
 *  1. the user asks, proving their password;
 *  2. a code sent to the CURRENT mailbox is answered;
 *  3. a code sent to the PROPOSED mailbox is answered, and only then is
 *     the user's row touched.
 *
 * The proposed address has no account authority while it waits here:
 * password resets and sign-in codes go to the user's real address until
 * the very last step completes. Both codes bind to this exact request —
 * changing the proposed address opens a new request and the old one dies,
 * so proof gathered for one address can never authorise another.
 */
class EmailChangeRequest extends Model
{
    /**
     * Codes remain valid for this many minutes after being sent.
     */
    public const CODE_LIFETIME_MINUTES = 10;

    /**
     * The request dies after this many wrong codes, across both steps.
     */
    public const MAX_ATTEMPTS = 3;

    /**
     * Seconds a user must wait between resends.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Resends allowed over the life of one request.
     */
    public const MAX_RESENDS = 3;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'proposed_email',
        'current_code_hash',
        'proposed_code_hash',
        'code_expires_at',
        'current_verified_at',
        'completed_at',
        'cancelled_at',
        'cancelled_reason',
        'attempts',
        'resends',
        'last_sent_at',
    ];

    /**
     * The hashes never leave the application.
     *
     * @var list<string>
     */
    protected $hidden = [
        'current_code_hash',
        'proposed_code_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code_expires_at' => 'datetime',
            'current_verified_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
            'resends' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this request is still open — neither finished, cancelled,
     * dead of wrong answers nor expired.
     */
    public function isUsable(): bool
    {
        return $this->completed_at === null
            && $this->cancelled_at === null
            && $this->attempts < self::MAX_ATTEMPTS
            && $this->code_expires_at->gte(Carbon::now());
    }

    /**
     * Whether the flow is waiting on the CURRENT mailbox's code.
     */
    public function awaitingCurrentMailbox(): bool
    {
        return $this->current_verified_at === null;
    }

    /**
     * Whether the flow is waiting on the PROPOSED mailbox's code.
     */
    public function awaitingProposedMailbox(): bool
    {
        return $this->current_verified_at !== null
            && $this->completed_at === null;
    }
}

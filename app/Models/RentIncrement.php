<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents one approved historical or future rent increment.
 *
 * RentIncrement records are immutable historical accounting/contractual
 * evidence once applied. The Lease's rent_amount contains only the current
 * rent, while this model preserves how that amount changed over time.
 */
class RentIncrement extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lease_id',
        'old_rent_amount',
        'increment_type',
        'increment_value',
        'new_rent_amount',
        'effective_date',
        'status',
        'notification_sent_at',
        'applied_at',
        'cancelled_at',
    ];

    /**
     * Convert persisted values to appropriate PHP representations.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_rent_amount' => 'integer',
            'increment_value' => 'decimal:2',
            'new_rent_amount' => 'integer',

            'effective_date' => 'date',

            'notification_sent_at' => 'datetime',
            'applied_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Lease whose rent is changed by this increment.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(
            Lease::class
        );
    }

    /**
     * Determine whether this increment is waiting to take effect.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Determine whether this increment has already been applied.
     */
    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    /**
     * Determine whether this increment was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Determine whether the tenant notification has been sent.
     */
    public function notificationWasSent(): bool
    {
        return $this->notification_sent_at !== null;
    }
}

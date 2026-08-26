<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents money received from a tenant under a Lease.
 *
 * Payments are kept separate from Invoices because one Payment may
 * settle several Invoices, while one Invoice may also be settled by
 * several Payments.
 */
class Payment extends Model
{
    use BelongsToOrganisation;

    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lease_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'collector_name',
        'cash_receiver_user_id',
        'cash_receiver_name',
        'notes',
        'is_opening_advance',
    ];

    /**
     * Convert stored values to appropriate PHP representations.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_date' => 'date',
            'is_opening_advance' => 'boolean',
        ];
    }

    /**
     * Lease under which this Payment was received.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Invoice allocations made from this Payment.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Total amount already allocated to invoices.
     */
    public function allocatedAmount(): int
    {
        return (int) $this->allocations()->sum('amount');
    }

    /**
     * Amount of the Payment not yet allocated.
     *
     * A positive value represents unapplied funds that may later be
     * consumed by another invoice or transferred to an advance/reserve
     * workflow.
     */
    public function unallocatedAmount(): int
    {
        return $this->amount - $this->allocatedAmount();
    }

    /**
     * Total unapplied Payment money already classified into tenant-held funds.
     *
     * Classified money remains unapplied to Invoices, but it is no longer
     * available for ordinary FIFO rent allocation.
     */
    public function classifiedFundAmount(): int
    {
        return (int) TenantFundTransaction::query()
            ->where('payment_id', $this->id)
            ->where('direction', 'credit')
            ->whereIn('category', [
                'reserve_funding',
                'advance_funding',
                'deposit_funding',
            ])
            ->sum('amount');
    }

    /**
     * Money that remains available for ordinary Invoice allocation.
     *
     * Formula:
     *
     * Payment amount
     * - Invoice allocations
     * - tenant-fund classifications
     */
    public function allocatableAmount(): int
    {
        return max(
            0,
            $this->amount
            - $this->allocatedAmount()
            - $this->classifiedFundAmount()
        );
    }

    public function cashReceiver()
    {
        return $this->belongsTo(
            User::class,
            'cash_receiver_user_id'
        );
    }
}

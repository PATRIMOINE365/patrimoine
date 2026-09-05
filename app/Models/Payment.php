<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use App\Services\Documents\DocumentNumberService;
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
        'receipt_number',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'collector_name',
        'cash_receiver_user_id',
        'cash_receiver_name',
        'notes',
        'is_opening_advance',
        'is_opening_deposit',
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
            'is_opening_deposit' => 'boolean',
        ];
    }

    /**
     * V1.0.50: every payment is numbered from the organisation's own
     * counter the moment it is recorded.
     *
     * The receipt used to be numbered from the payment's database id,
     * which is allocated across every organisation on the installation:
     * a customer's first receipt read RCT-000142, and a tenant holding
     * two of them could work out how much money the whole platform
     * takes. Payments recorded before this release keep the number their
     * receipt already carried — a reference that has been on a document
     * must never change under it.
     */
    protected static function booted(): void
    {
        static::creating(
            function (Payment $payment): void {
                if (
                    $payment->receipt_number !== null
                    && $payment->receipt_number !== ''
                ) {
                    return;
                }

                $payment->receipt_number =
                    app(DocumentNumberService::class)->next('RCT');
            }
        );
    }

    /**
     * The number printed on this payment's receipt.
     *
     * Falls back to the pre-V1.0.50 shape for any row that somehow has
     * none, so a receipt can always be rendered.
     */
    public function receiptNumber(): string
    {
        $number = trim((string) $this->receipt_number);

        return $number !== ''
            ? $number
            : sprintf('RCT-%06d', (int) $this->id);
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

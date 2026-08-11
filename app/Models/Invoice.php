<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a billing document issued under a Lease.
 *
 * An Invoice records what the tenant owes. Settlement may come from:
 *
 * - normal tenant Payments allocated through PaymentAllocation; or
 * - Rent Reserve consumption during the termination-notice period.
 *
 * The remaining balance is always derived from those settlement records
 * rather than stored as a mutable balance column.
 */
class Invoice extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lease_id',
        'invoice_number',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'status',
        'total_amount',
        'vat_rate',
        'net_amount',
        'vat_amount',
        'proration_amount',
        'notes',
    ];

    /**
     * Convert stored values to appropriate PHP representations.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'integer',
            'vat_rate' => 'decimal:2',
            'net_amount' => 'integer',
            'vat_amount' => 'integer',
            'proration_amount' => 'integer',
        ];
    }

    /**
     * Lease that generated this Invoice.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Normal cash Payment allocations applied to this Invoice.
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Tenant-fund ledger movements associated with this Invoice.
     *
     * Rent Reserve consumption is recorded here as a debit transaction.
     */
    public function tenantFundTransactions(): HasMany
    {
        return $this->hasMany(TenantFundTransaction::class);
    }

    /**
     * Amount settled through ordinary tenant Payments.
     */
    public function paymentPaidAmount(): int
    {
        return (int) $this->paymentAllocations()
            ->sum('amount');
    }

    /**
     * Amount settled through Rent Reserve consumption.
     */
    public function reservePaidAmount(): int
    {
        return (int) $this->tenantFundTransactions()
            ->where('direction', 'debit')
            ->where('category', 'rent_consumption')
            ->sum('amount');
    }

    /**
     * Amount settled through Consumable Advance.
     */
    public function advancePaidAmount(): int
    {
        return (int) $this->tenantFundTransactions()
            ->where('direction', 'debit')
            ->where('category', 'advance_consumption')
            ->sum('amount');
    }

    /**
     * Total amount settled against this Invoice.
     *
     * Settlement may come from:
     * - ordinary tenant Payments;
     * - Rent Reserve;
     * - Consumable Advance.
     */
    public function paidAmount(): int
    {
        return $this->paymentPaidAmount()
            + $this->reservePaidAmount()
            + $this->advancePaidAmount();
    }

    /**
     * Amount still outstanding on this Invoice.
     */
    public function outstandingAmount(): int
    {
        return max(
            0,
            $this->total_amount - $this->paidAmount()
        );
    }

    /**
     * Determine whether the Invoice has been fully settled.
     */
    public function isFullyPaid(): bool
    {
        return $this->outstandingAmount() === 0;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Represents the final security-deposit close-out for a Lease.
 *
 * The settlement records immutable accounting snapshots used for
 * reporting and refund-voucher generation.
 */
class SecurityDepositSettlement extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lease_id',
        'deposit_amount',
        'deduction_amount',
        'refund_amount',
        'tenant_debt_amount',
        'debt_invoice_id',
        'settlement_date',
        'refund_voucher_number',
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
            'deposit_amount' => 'integer',
            'deduction_amount' => 'integer',
            'refund_amount' => 'integer',
            'tenant_debt_amount' => 'integer',
            'settlement_date' => 'date',
        ];
    }

    /**
     * Lease being settled.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Receivable created when final Security Deposit deductions exceed
     * the money actually held for the tenant.
     *
     * NULL means the settlement produced no tenant debt.
     */
    public function debtInvoice(): BelongsTo
    {
        return $this->belongsTo(
            related: Invoice::class,
            foreignKey: 'debt_invoice_id'
        );
    }
}

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
        'type',
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
     * Itemized lines of an expense Invoice.
     *
     * Rent invoices have none; their contractual amount is the Invoice.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
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
        /*
         * V1.0.9: when the relation has been eager-loaded (dashboard
         * list queries), sum the loaded collection instead of issuing
         * one query per Invoice.
         */
        if ($this->relationLoaded('paymentAllocations')) {
            return (int) $this->paymentAllocations->sum('amount');
        }

        return (int) $this->paymentAllocations()
            ->sum('amount');
    }

    /**
     * Amount settled through Rent Reserve consumption.
     *
     * Cancelled payments appear as credit reversal rows in the same
     * category, so the net of debits minus credits is what actually
     * remains applied to this Invoice.
     */
    public function reservePaidAmount(): int
    {
        return $this->fundSettledAmount('rent_consumption');
    }

    /**
     * Amount settled through Consumable Advance.
     */
    public function advancePaidAmount(): int
    {
        return $this->fundSettledAmount('advance_consumption');
    }

    /**
     * Amount of an expense Invoice settled from tenant fund accounts.
     */
    public function expenseSettledAmount(): int
    {
        return $this->fundSettledAmount('expense_settlement');
    }

    /**
     * Net amount applied to this Invoice in one fund-ledger category.
     */
    private function fundSettledAmount(
        string $category
    ): int {
        /*
         * V1.0.9: prefer the eager-loaded collection so dashboard list
         * queries do not re-query per Invoice and per category.
         */
        if ($this->relationLoaded('tenantFundTransactions')) {
            $transactions = $this->tenantFundTransactions
                ->where('category', $category);

            $debits = (int) $transactions
                ->where('direction', 'debit')
                ->sum('amount');

            $credits = (int) $transactions
                ->where('direction', 'credit')
                ->sum('amount');

            return $debits - $credits;
        }

        $debits = (int) $this->tenantFundTransactions()
            ->where('direction', 'debit')
            ->where('category', $category)
            ->sum('amount');

        $credits = (int) $this->tenantFundTransactions()
            ->where('direction', 'credit')
            ->where('category', $category)
            ->sum('amount');

        return $debits - $credits;
    }

    /**
     * Manual Security Deposit applications applied to this Invoice.
     */
    public function securityDepositApplications(): HasMany
    {
        return $this->hasMany(SecurityDepositApplication::class);
    }

    /**
     * Amount settled through manual Security Deposit application.
     */
    public function securityDepositAppliedAmount(): int
    {
        // V1.0.9: see paymentPaidAmount() — avoid the per-Invoice query.
        if ($this->relationLoaded('securityDepositApplications')) {
            return (int) $this->securityDepositApplications->sum('amount');
        }

        return (int) $this->securityDepositApplications()
            ->sum('amount');
    }

    /**
     * Total amount settled against this Invoice.
     *
     * Settlement may come from:
     * - ordinary tenant Payments;
     * - Rent Reserve;
     * - Consumable Advance;
     * - manual Security Deposit application.
     */
    public function paidAmount(): int
    {
        return $this->paymentPaidAmount()
            + $this->reservePaidAmount()
            + $this->advancePaidAmount()
            + $this->expenseSettledAmount()
            + $this->securityDepositAppliedAmount();
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

    /**
     * Determine whether this Invoice represents ordinary contractual rent.
     *
     * Only rent invoices participate in owner rent entitlement and management
     * fee accounting when tenant cash is collected.
     */
    public function isRentInvoice(): bool
    {
        return $this->type === 'rent';
    }

    /**
     * Determine whether this Invoice bills an itemized expense.
     */
    public function isExpenseInvoice(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Determine whether this Invoice represents Security Deposit close-out debt.
     *
     * This is a tenant receivable but is not rent revenue.
     */
    public function isSecurityDepositDebtInvoice(): bool
    {
        return $this->type === 'security_deposit_debt';
    }
}

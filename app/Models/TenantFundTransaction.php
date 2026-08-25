<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents one auditable movement of tenant-held money.
 *
 * Transactions should normally be created through financial services
 * rather than directly from controllers so Patrimoine can consistently
 * enforce rules such as:
 *
 * - preventing negative Rent Reserve balances;
 * - restricting Rent Reserve consumption until termination notice;
 * - validating security-deposit deductions;
 * - preventing duplicate use of the same funds.
 */
class TenantFundTransaction extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_fund_account_id',
        'payment_id',
        'invoice_id',
        'direction',
        'category',
        'amount',
        'transaction_date',
        'payment_method',
        'reference',
        'notes',
        'reversal_of_transaction_id',
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
            'transaction_date' => 'date',
        ];
    }

    /**
     * Tenant fund account affected by this transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(
            related: TenantFundAccount::class,
            foreignKey: 'tenant_fund_account_id'
        );
    }

    /**
     * Optional tenant Payment from which these funds originated.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Optional Invoice settled or affected by this transaction.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Transaction this reversal cancels, when this row is a reversal.
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reversal_of_transaction_id'
        );
    }

    /**
     * Owner rent entitlement released by this consumption.
     *
     * Only consumptions recorded since V1.0.8 tag their entitlement;
     * the tag is what makes a payment safely cancellable.
     */
    public function ownerEntitlements(): HasMany
    {
        return $this->hasMany(
            OwnerTransaction::class,
            'tenant_fund_transaction_id'
        );
    }

    /**
     * Determine whether this transaction has been cancelled.
     *
     * A transaction is cancelled exactly when a reversal row points at
     * it; the original row itself is never modified.
     */
    public function isReversed(): bool
    {
        return self::query()
            ->where(
                'reversal_of_transaction_id',
                $this->id
            )
            ->exists();
    }
}

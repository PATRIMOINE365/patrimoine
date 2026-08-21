<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents one auditable movement in an owner's financial ledger.
 */
class OwnerTransaction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_account_id',
        'building_id',
        'unit_id',
        'lease_id',
        'invoice_id',
        'payment_allocation_id',
        'direction',
        'category',
        'amount',
        'transaction_date',
        'payment_method',
        'deposit_purpose',
        'collector_name',
        'cash_receiver_user_id',
        'cash_receiver_name',
        'reference',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    public function ownerAccount(): BelongsTo
    {
        return $this->belongsTo(OwnerAccount::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Payout allocations that consumed this owner credit.
     */
    public function payoutAllocations(): HasMany
    {
        return $this->hasMany(OwnerPayoutAllocation::class);
    }

    /**
     * Tenant PaymentAllocation that generated this owner transaction,
     * when the transaction represents collected rent entitlement.
     */
    public function paymentAllocation(): BelongsTo
    {
        return $this->belongsTo(PaymentAllocation::class);
    }

    public function cashReceiver()
    {
        return $this->belongsTo(
            User::class,
            'cash_receiver_user_id'
        );
    }
}

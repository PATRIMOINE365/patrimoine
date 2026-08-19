<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'tenant_fund_transaction_id',
        'tenant_fund_account_id',
        'lease_id',
        'tenant_id',
        'fund_type',
        'amount',
        'payment_method',
        'transaction_date',
        'reference',
        'notes',
        'tenant_name',
        'lease_label',
        'building_label',
        'unit_label',
        'performed_by_user_id',
        'performed_by_name',
    ];

    protected $casts = [
        'amount' => 'integer',

        'transaction_date' => 'date',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            TenantFundTransaction::class,
            'tenant_fund_transaction_id'
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            TenantFundAccount::class,
            'tenant_fund_account_id'
        );
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(
            Lease::class
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Party::class,
            'tenant_id'
        );
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by_user_id'
        );
    }
}

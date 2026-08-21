<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable operational record of held Security Deposit applied directly
 * against a Lease receivable.
 *
 * This is deliberately not a Payment: no new Cash, Bank, or Mobile Payment
 * asset enters Patrimoine when previously held Security Deposit is applied.
 */
class SecurityDepositApplication extends Model
{
    protected $fillable = [
        'lease_id',
        'invoice_id',
        'tenant_fund_transaction_id',
        'amount',
        'application_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'application_date' => 'date',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenantFundTransaction(): BelongsTo
    {
        return $this->belongsTo(TenantFundTransaction::class);
    }
}

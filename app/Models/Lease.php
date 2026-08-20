<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents the contractual agreement for renting one Unit
 * to one tenant.
 *
 * The Lease contains contractual terms and lifecycle state.
 *
 * It deliberately does not store mutable financial balances.
 * Actual money received and held on behalf of a Tenant remains in the
 * financial ledger through Payments, TenantFundAccounts and
 * TenantFundTransactions.
 */
class Lease extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'unit_id',
        'tenant_id',
        'agent_id',
        'start_date',
        'end_date',
        'status',
        'termination_notice_date',
        'termination_date',
        'termination_final_rent_mode',
        'termination_previous_status',
        'termination_completed_at',
        'rent_amount',
        'payment_frequency',
        'due_day',
        'vat_rate',
        'proration_amount',
        'security_deposit_amount',

        /*
         * Contractual tenant advance terms.
         *
         * These are expectations agreed in the Lease and not actual
         * tenant-fund account balances.
         */
        'advance_payment_amount',
        'rent_reserve_amount',

        /*
         * Contractual rent-increment configuration.
         */
        'rent_increment_type',
        'rent_increment_value',
        'next_rent_increment_date',

        /*
         * Managing organisation fee.
         */
        'management_fee_type',
        'management_fee_value',

        'agent_commission_amount',
        'notes',
    ];

    /**
     * Convert database values to appropriate PHP representations.
     *
     * Monetary values use integers because Patrimoine V1 stores whole
     * currency units only.
     *
     * Percentage/configuration values use decimal casts so their precision
     * is retained without relying on binary floating-point storage.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'termination_notice_date' => 'date',
            'termination_date' => 'date',
            'termination_completed_at' => 'datetime',
            'next_rent_increment_date' => 'date',

            'rent_amount' => 'integer',
            'due_day' => 'integer',

            'vat_rate' => 'decimal:2',

            'proration_amount' => 'integer',
            'security_deposit_amount' => 'integer',

            'advance_payment_amount' => 'integer',
            'rent_reserve_amount' => 'integer',

            'rent_increment_value' => 'decimal:2',

            'management_fee_value' => 'decimal:2',

            'agent_commission_amount' => 'integer',
        ];
    }

    /**
     * Unit covered by this Lease.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Party renting the Unit.
     *
     * Patrimoine V1 supports exactly one tenant per Lease.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            related: Party::class,
            foreignKey: 'tenant_id'
        );
    }

    /**
     * Optional Party acting as Agent for this Lease.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(
            related: Party::class,
            foreignKey: 'agent_id'
        );
    }

    /**
     * Determine the contractual day of the month on which rent is due.
     *
     * When no explicit override exists, the Lease start-date day is used.
     */
    public function effectiveDueDay(): int
    {
        return $this->due_day
            ?? $this->start_date->day;
    }

    /**
     * Return the contractual portion of the Advance Payment that is
     * available as Consumable Advance.
     *
     * This is a contractual figure only.
     *
     * Actual available tenant funds must always be obtained from the
     * tenant-fund ledger.
     */
    public function contractualConsumableAdvanceAmount(): int
    {
        return max(
            0,
            $this->advance_payment_amount
                - $this->rent_reserve_amount
        );
    }

    /**
     * Invoices generated under this Lease.
     *
     * Historical Invoices remain independent snapshots of the contractual
     * values that applied when they were issued.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Payments received under this Lease.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Tenant-held financial accounts associated with this Lease.
     *
     * These contain actual accounted tenant money:
     *
     * - Rent Reserve;
     * - Consumable Advance;
     * - Security Deposit.
     */
    public function tenantFundAccounts(): HasMany
    {
        return $this->hasMany(
            TenantFundAccount::class
        );
    }

    /**
     * Itemized charges applied against this Lease's Security Deposit.
     */
    public function securityDepositDeductions(): HasMany
    {
        return $this->hasMany(
            SecurityDepositDeduction::class
        );
    }

    /**
     * Final Security Deposit close-out for this Lease.
     */
    public function securityDepositSettlement(): HasOne
    {
        return $this->hasOne(
            SecurityDepositSettlement::class
        );
    }

    /**
     * Immutable contractual term history for this Lease.
     */
    public function termVersions(): HasMany
    {
        return $this->hasMany(
            LeaseTermVersion::class
        )->orderBy('version_number');
    }

    /**
     * Historical and scheduled rent increments for this Lease.
     *
     * The Lease stores only the current contractual rent. RentIncrement records
     * preserve the complete history of approved, applied, or cancelled changes.
     */
    public function rentIncrements(): HasMany
    {
        return $this->hasMany(
            RentIncrement::class
        );
    }
}

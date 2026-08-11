<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents the contractual agreement for renting one Unit
 * to one tenant.
 *
 * The Lease model contains contractual terms and lifecycle state.
 * It does not represent the financial ledger.
 *
 * Future financial entities will reference the Lease for:
 * - invoices;
 * - payments;
 * - rent reserve movements;
 * - security deposit movements;
 * - management fees;
 * - agent commission deductions;
 * - owner accounting.
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
        'rent_amount',
        'payment_frequency',
        'due_day',
        'vat_rate',
        'proration_amount',
        'security_deposit_amount',
        'management_fee_type',
        'management_fee_value',
        'agent_commission_amount',
        'notes',
    ];

    /**
     * Convert database values to appropriate PHP representations.
     *
     * Monetary amounts remain integers because Patrimoine does not
     * use fractional currency values.
     *
     * Decimal percentage/configuration fields remain fixed-precision
     * decimal strings so floating-point rounding is avoided.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'termination_notice_date' => 'date',

            'rent_amount' => 'integer',
            'due_day' => 'integer',
            'vat_rate' => 'decimal:2',
            'proration_amount' => 'integer',
            'security_deposit_amount' => 'integer',
            'management_fee_value' => 'decimal:2',
            'agent_commission_amount' => 'integer',
        ];
    }

    /**
     * Unit covered by this lease.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Party renting the Unit.
     *
     * Patrimoine 1.0 supports exactly one tenant per lease.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            related: Party::class,
            foreignKey: 'tenant_id'
        );
    }

    /**
     * Optional Party acting as Agent for this lease.
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
     * If no explicit override exists, the day is inherited from the
     * lease start date.
     */
    public function effectiveDueDay(): int
    {
        return $this->due_day
            ?? $this->start_date->day;
    }
    /**
     * Invoices generated under this Lease.
     *
     * Historical invoices remain independent snapshots of the contractual
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
}

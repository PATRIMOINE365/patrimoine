<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a billing document issued under a Lease.
 *
 * An Invoice records what the tenant owes. It does not record how
 * money was received. Payment and allocation records will later
 * settle invoices independently, which allows Patrimoine to support
 * partial payments and FIFO allocation.
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
     * Lease that generated this invoice.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}

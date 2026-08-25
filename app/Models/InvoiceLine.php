<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One itemized line of an expense Invoice.
 *
 * Rent invoices have no lines; their single contractual amount lives on
 * the Invoice itself. Expense invoices are human-entered batches of
 * description + amount lines whose sum is the Invoice total.
 */
class InvoiceLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /**
     * Invoice this line belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}

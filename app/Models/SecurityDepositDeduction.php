<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents one itemized deduction against a Lease's security deposit.
 */
class SecurityDepositDeduction extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lease_id',
        'description',
        'amount',
        'deduction_date',
        'reference',
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
            'amount' => 'integer',
            'deduction_date' => 'date',
        ];
    }

    /**
     * Lease against whose deposit this deduction applies.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}

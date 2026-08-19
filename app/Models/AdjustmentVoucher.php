<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentVoucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'account_type',
        'account_id',
        'entity_type',
        'entity_id',
        'entity_label',
        'account_label',
        'previous_balance',
        'corrected_balance',
        'difference',
        'reason',
        'adjustment_date',
        'performed_by_user_id',
        'performed_by_name',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'previous_balance' => 'integer',
            'corrected_balance' => 'integer',
            'difference' => 'integer',
            'adjustment_date' => 'date',
        ];
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by_user_id'
        );
    }
}

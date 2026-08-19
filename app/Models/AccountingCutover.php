<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingCutover extends Model
{
    public const V105_OPENING_BALANCE =
        'v1.0.5-opening-balance';

    public const STATUS_RUNNING =
        'running';

    public const STATUS_COMPLETED =
        'completed';

    protected $fillable = [
        'cutover_key',
        'cutover_date',
        'status',
        'position_count',
        'journal_entry_count',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'cutover_date' =>
                'date',

            'completed_at' =>
                'datetime',

            'position_count' =>
                'integer',

            'journal_entry_count' =>
                'integer',

            'metadata' =>
                'array',
        ];
    }
}

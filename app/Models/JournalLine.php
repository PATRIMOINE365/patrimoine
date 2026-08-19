<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable debit or credit line belonging to a Financial Journal entry.
 */
class JournalLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_entry_id',
        'accounting_account_id',
        'debit_amount',
        'credit_amount',
        'account_code_snapshot',
        'account_name_snapshot',
        'account_type_snapshot',
        'memo',
        'snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit_amount' => 'integer',
            'credit_amount' => 'integer',
            'snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException(
                'Posted Journal lines are immutable.'
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Posted Journal lines cannot be deleted.'
            );
        });
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class);
    }
}

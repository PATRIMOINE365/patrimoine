<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Immutable header for one posted Financial Journal transaction.
 *
 * Patrimoine's Financial Journal is append-only. Corrections are represented
 * by new reversal/correcting entries rather than changes to existing entries.
 */
class JournalEntry extends Model
{

    public const KIND_FINANCIAL = 'financial';

    public const KIND_REVERSAL = 'reversal';

    public const KIND_INFORMATIONAL = 'informational';


    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_number',
        'entry_kind',
        'reversal_of_id',
        'reversed_by_id',
        'reversal_reason',
        'journal_date',
        'posted_at',
        'transaction_type',
        'description',
        'source_type',
        'source_id',
        'actor_user_id',
        'actor_name_snapshot',
        'snapshot',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'posted_at' => 'datetime',
            'snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException(
                'Posted Journal entries are immutable.'
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Posted Journal entries cannot be deleted.'
            );
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_user_id'
        );
    }

    /**
     * Sum of all debit lines belonging to this Journal transaction.
     */
    public function debitTotal(): int
    {
        if ($this->relationLoaded('lines')) {
            return (int) $this->lines->sum('debit_amount');
        }

        return (int) $this->lines()->sum('debit_amount');
    }

    /**
     * Sum of all credit lines belonging to this Journal transaction.
     */
    public function creditTotal(): int
    {
        if ($this->relationLoaded('lines')) {
            return (int) $this->lines->sum('credit_amount');
        }

        return (int) $this->lines()->sum('credit_amount');
    }

    /**
     * Every normal Financial Journal transaction must balance.
     */
    public function isBalanced(): bool
    {
        return $this->debitTotal() === $this->creditTotal();
    }

    public function reversedEntry()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversalEntry()
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function isFinancial(): bool
    {
        return $this->entry_kind === self::KIND_FINANCIAL;
    }

    public function isReversal(): bool
    {
        return $this->entry_kind === self::KIND_REVERSAL;
    }

    public function isInformational(): bool
    {
        return $this->entry_kind === self::KIND_INFORMATIONAL;
    }

    public function isReversed(): bool
    {
        return $this->reversed_by_id !== null;
    }

}

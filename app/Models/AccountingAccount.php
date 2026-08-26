<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;

/**
 * System-managed account in Patrimoine's internal Chart of Accounts.
 *
 * V1.0.5 introduces proper double-entry accounting underneath the existing
 * operational financial ledgers. These accounts are application-managed
 * infrastructure and are not user-created accounting records.
 */
class AccountingAccount extends Model
{
    use BelongsToOrganisation;

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_CLEARING = 'clearing';

    public const NORMAL_DEBIT = 'debit';

    public const NORMAL_CREDIT = 'credit';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'is_system',
        'is_active',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

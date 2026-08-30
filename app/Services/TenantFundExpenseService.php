<?php

namespace App\Services;

use App\Models\TenantFundAccount;
use App\Services\Documents\DocumentNumberService;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V1.0.8: a lease-specific expense settled from a tenant fund account.
 *
 * An expense is a batch of one or more description+amount lines sharing
 * one TEX- voucher number, exactly like owner expense bills. Any of the
 * three fund accounts may be the source, but the account can NEVER go
 * negative: a batch whose total exceeds the held balance is rejected.
 */
class TenantFundExpenseService
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    /**
     * @param  array<int, array{description: mixed, amount: mixed}>  $lines
     * @return Collection<int, TenantFundTransaction>
     */
    public function expense(
        int $accountId,
        array $lines,
        string $transactionDate,
        string $paymentMethod,
        ?string $reference = null,
    ): Collection {
        return DB::transaction(
            function () use (
                $accountId,
                $lines,
                $transactionDate,
                $paymentMethod,
                $reference,
            ): Collection {
                $account =
                    TenantFundAccount::query()
                        ->whereKey($accountId)
                        ->lockForUpdate()
                        ->firstOrFail();

                if ($account->status !== 'active') {
                    throw new RuntimeException(
                        __('business.tenant.expense_account_inactive')
                    );
                }

                $lines = array_values($lines);

                if ($lines === []) {
                    throw new RuntimeException(
                        __('business.tenant.expense_lines_required')
                    );
                }

                $total = 0;

                foreach ($lines as $line) {
                    $amount = (int) ($line['amount'] ?? 0);

                    if ($amount <= 0) {
                        throw new RuntimeException(
                            __('business.tenant.expense_positive')
                        );
                    }

                    if (trim((string) ($line['description'] ?? '')) === '') {
                        throw new RuntimeException(
                            __('business.tenant.expense_description_required')
                        );
                    }

                    $total += $amount;
                }

                if ($total > $account->balance()) {
                    throw new RuntimeException(
                        __('business.tenant.expense_exceeds_balance')
                    );
                }

                $voucherNumber = $this->nextNumber();

                $suffix =
                    $reference !== null
                    && trim($reference) !== ''
                        ? ' ['.trim($reference).']'
                        : '';

                return collect($lines)->map(
                    fn (array $line): TenantFundTransaction => TenantFundTransaction::create([
                        'tenant_fund_account_id' => $account->id,

                        'payment_id' => null,

                        'invoice_id' => null,

                        'direction' => 'debit',

                        'category' => 'expense',

                        'amount' => (int) $line['amount'],

                        'transaction_date' => $transactionDate,

                        'payment_method' => $paymentMethod,

                        'reference' => $voucherNumber,

                        'notes' => trim((string) $line['description']).$suffix,
                    ])
                );
            }
        );
    }

    /**
     * TEX-YYYY-NNNNNN.
     *
     * V1.0.36: from the shared counter, and the year is now part of the
     * number. Reading the highest TEX- reference already written meant
     * that deleting the newest expense handed its number to the next.
     */
    private function nextNumber(): string
    {
        return $this->numbers->next('TEX');
    }
}

<?php

namespace App\Services;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V1.0.8: a lease-specific expense settled from a tenant fund account.
 *
 * Any of the three fund accounts may be the source, but the account can
 * NEVER go negative: an expense beyond the held balance is rejected.
 * The generated TEX- reference doubles as the voucher number.
 */
class TenantFundExpenseService
{
    public function expense(
        int $accountId,
        int $amount,
        string $transactionDate,
        string $paymentMethod,
        string $description,
        ?string $reference = null,
    ): TenantFundTransaction {
        return DB::transaction(
            function () use (
                $accountId,
                $amount,
                $transactionDate,
                $paymentMethod,
                $description,
                $reference,
            ): TenantFundTransaction {
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

                if ($amount <= 0) {
                    throw new RuntimeException(
                        __('business.tenant.expense_positive')
                    );
                }

                if (trim($description) === '') {
                    throw new RuntimeException(
                        __('business.tenant.expense_description_required')
                    );
                }

                if ($amount > $account->balance()) {
                    throw new RuntimeException(
                        __('business.tenant.expense_exceeds_balance')
                    );
                }

                return TenantFundTransaction::create([
                    'tenant_fund_account_id' => $account->id,

                    'payment_id' => null,

                    'invoice_id' => null,

                    'direction' => 'debit',

                    'category' => 'expense',

                    'amount' => $amount,

                    'transaction_date' => $transactionDate,

                    'payment_method' => $paymentMethod,

                    'reference' => $this->nextNumber(),

                    'notes' => trim($description)
                        .(
                            $reference !== null
                            && trim($reference) !== ''
                                ? ' ['.trim($reference).']'
                                : ''
                        ),
                ]);
            }
        );
    }

    /**
     * Sequential TEX- number; the row lock during generation prevents
     * duplicates under concurrency.
     */
    private function nextNumber(): string
    {
        $prefix = 'TEX-';

        $last = TenantFundTransaction::query()
            ->where('category', 'expense')
            ->where('reference', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('reference');

        $next = 1;

        if (is_string($last)
            && preg_match('/^TEX-(\d+)$/', $last, $matches)
        ) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $next);
    }
}

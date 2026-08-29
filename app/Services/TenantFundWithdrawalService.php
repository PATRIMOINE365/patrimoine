<?php

namespace App\Services;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantFundWithdrawalService
{
    public function withdraw(
        int $accountId,
        int $amount,
        string $transactionDate,
        string $paymentMethod,
        ?string $reference = null,
        ?string $notes = null,
    ): TenantFundTransaction {
        return DB::transaction(
            function () use (
                $accountId,
                $amount,
                $transactionDate,
                $paymentMethod,
                $reference,
                $notes,
            ): TenantFundTransaction {
                /*
                 * Serialize withdrawals against this Account so two
                 * simultaneous requests cannot approve themselves from the
                 * same pre-withdrawal balance.
                 */
                $account =
                    TenantFundAccount::query()
                        ->whereKey($accountId)
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * These four refusals are things a person does, not
                 * programming faults, so they reach the customer as the
                 * text of a 422. Written as English literals they arrived
                 * in English whatever the organisation's language, and
                 * ErrorCodes could not recognise a sentence belonging to
                 * no catalogue — so no code was attached and support had
                 * nothing to ask for. They are catalogued now.
                 */
                if ($account->status !== 'active') {
                    throw new RuntimeException(
                        __('business.tenant_fund_withdrawal.account_inactive')
                    );
                }

                /*
                 * General Phase 5 Withdrawal initially covers funds that can
                 * normally be paid back directly.
                 *
                 * Security Deposit remains governed by its controlled
                 * settlement/refund workflow until the Phase 9 termination
                 * workflow unifies that final payout with Withdrawal.
                 */
                if (
                    ! in_array(
                        $account->type,
                        [
                            'rent_reserve',
                            'consumable_advance',
                        ],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        __('business.tenant_fund_withdrawal.ineligible_fund')
                    );
                }

                if ($amount <= 0) {
                    throw new RuntimeException(
                        __('business.tenant_fund_withdrawal.amount_positive')
                    );
                }

                $balance =
                    $account->balance();

                if ($amount > $balance) {
                    throw new RuntimeException(
                        __('business.tenant_fund_withdrawal.exceeds_balance')
                    );
                }

                return TenantFundTransaction::create([
                    'tenant_fund_account_id' => $account->id,

                    'payment_id' => null,

                    'invoice_id' => null,

                    'direction' => 'debit',

                    'category' => 'withdrawal',

                    'amount' => $amount,

                    'transaction_date' => $transactionDate,

                    'payment_method' => $paymentMethod,

                    'reference' => $reference,

                    'notes' => $notes,
                ]);
            }
        );
    }
}

<?php

namespace App\Services;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\User;
use App\Services\Accounting\TenantFundTransferJournalService;
use App\Services\Documents\TransferVoucherNumberService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Move held tenant money between two fund accounts of the SAME tenant.
 *
 * The two accounts may belong to different Leases of that tenant. The
 * Transfer is recorded as one debit leg on the source account and one
 * credit leg on the destination account, sharing a single generated
 * TRF reference that doubles as the Transfer voucher number.
 *
 * No money enters or leaves Patrimoine during a Transfer.
 */
class TenantFundTransferService
{
    public function __construct(
        private readonly TransferVoucherNumberService $numbers,
        private readonly TenantFundTransferJournalService $journal,
    ) {}

    /**
     * @return array{
     *     debit_transaction: TenantFundTransaction,
     *     credit_transaction: TenantFundTransaction,
     *     source_balance: int,
     *     destination_balance: int,
     * }
     */
    public function transfer(
        TenantFundAccount $source,
        TenantFundAccount $destination,
        int $amount,
        string $reason,
        ?string $reference,
        User $actor,
    ): array {
        return DB::transaction(
            function () use (
                $source,
                $destination,
                $amount,
                $reason,
                $reference,
                $actor,
            ): array {
                if ($source->id === $destination->id) {
                    throw new RuntimeException(
                        'Transfer source and destination must be different Tenant fund accounts.'
                    );
                }

                /*
                 * Serialize the Transfer against BOTH accounts so two
                 * simultaneous requests cannot approve themselves from the
                 * same pre-transfer balances.
                 *
                 * Always lock in ascending account id order so two opposite
                 * concurrent transfers between the same pair of accounts
                 * cannot deadlock each other.
                 */
                $locked = TenantFundAccount::query()
                    ->whereKey([
                        $source->id,
                        $destination->id,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $source =
                    $locked->get(
                        $source->id
                    );

                $destination =
                    $locked->get(
                        $destination->id
                    );

                if (
                    $source === null
                    || $destination === null
                ) {
                    throw new RuntimeException(
                        'One of the selected Tenant fund accounts no longer exists.'
                    );
                }

                if ($source->status !== 'active') {
                    throw new RuntimeException(
                        'The source Tenant fund account is not active.'
                    );
                }

                if ($destination->status !== 'active') {
                    throw new RuntimeException(
                        'The destination Tenant fund account is not active.'
                    );
                }

                $source->loadMissing(
                    'lease'
                );

                $destination->loadMissing(
                    'lease'
                );

                /*
                 * A Transfer may cross Leases, but never tenants. Money held
                 * for one tenant must not silently become money held for
                 * another tenant.
                 */
                if (
                    $source->lease === null
                    || $destination->lease === null
                    || $source->lease->tenant_id
                        !== $destination->lease->tenant_id
                ) {
                    throw new RuntimeException(
                        'Both Tenant fund accounts must belong to the same tenant.'
                    );
                }

                if ($amount <= 0) {
                    throw new RuntimeException(
                        'Transfer amount must be greater than zero.'
                    );
                }

                /*
                 * The balance remains ledger-derived:
                 *
                 *     creditedAmount() - debitedAmount()
                 */
                $sourceBalance =
                    $source->balance();

                if ($amount > $sourceBalance) {
                    throw new RuntimeException(
                        'Transfer amount exceeds the available source Tenant fund balance.'
                    );
                }

                if (trim($reason) === '') {
                    throw new RuntimeException(
                        'Transfer reason is required.'
                    );
                }

                /*
                 * Both legs share one generated TRF number. That number is
                 * the Transfer voucher number on the downloadable PDF.
                 */
                $voucherNumber =
                    $this->numbers->next();

                $transactionDate =
                    now()->toDateString();

                $debitTransaction =
                    TenantFundTransaction::create([
                        'tenant_fund_account_id' => $source->id,

                        'payment_id' => null,

                        'invoice_id' => null,

                        'direction' => 'debit',

                        'category' => 'transfer',

                        'amount' => $amount,

                        'transaction_date' => $transactionDate,

                        'reference' => $voucherNumber,

                        'notes' => $reason,
                    ]);

                $creditTransaction =
                    TenantFundTransaction::create([
                        'tenant_fund_account_id' => $destination->id,

                        'payment_id' => null,

                        'invoice_id' => null,

                        'direction' => 'credit',

                        'category' => 'transfer',

                        'amount' => $amount,

                        'transaction_date' => $transactionDate,

                        'reference' => $voucherNumber,

                        'notes' => $reason,
                    ]);

                /*
                 * Mirror the operational Transfer into the immutable
                 * Financial Journal as ONE balanced liability reclassification
                 * when the V1.0.5 accounting runtime is active.
                 */
                $this->journal->post(
                    debitTransaction: $debitTransaction,

                    creditTransaction: $creditTransaction,

                    reason: trim($reason),

                    actor: $actor,

                    context: [
                        'external_reference' => $reference,
                    ],
                );

                return [
                    'debit_transaction' => $debitTransaction,

                    'credit_transaction' => $creditTransaction,

                    'source_balance' => $source
                        ->fresh()
                        ->balance(),

                    'destination_balance' => $destination
                        ->fresh()
                        ->balance(),
                ];
            }
        );
    }
}

<?php

namespace App\Services\Documents;

use App\Models\TenantFundTransaction;
use App\Models\User;
use App\Models\WithdrawalReceipt;
use RuntimeException;

class WithdrawalReceiptService
{
    public function __construct(
        private readonly WithdrawalReceiptNumberService $numbers,
    ) {}

    public function create(
        TenantFundTransaction $transaction,
        ?User $actor,
    ): WithdrawalReceipt {
        $transaction->loadMissing(
            'account.lease.tenant',
            'account.lease.unit.building'
        );

        $account =
            $transaction->account;

        $lease =
            $account?->lease;

        $tenant =
            $lease?->tenant;

        if (
            $transaction->category !== 'withdrawal'
            || $transaction->direction !== 'debit'
            || $account === null
            || $lease === null
            || $tenant === null
        ) {
            throw new RuntimeException(
                'Withdrawal Receipt requires a valid Tenant Withdrawal transaction.'
            );
        }

        return WithdrawalReceipt::create([
            'receipt_number' => $this->numbers->next(),

            'tenant_fund_transaction_id' => $transaction->id,

            'tenant_fund_account_id' => $account->id,

            'lease_id' => $lease->id,

            'tenant_id' => $tenant->id,

            'fund_type' => $account->type,

            'amount' => (int) $transaction->amount,

            'payment_method' => $transaction->payment_method,

            'transaction_date' => $transaction->transaction_date,

            'reference' => $transaction->reference,

            'notes' => $transaction->notes,

            'tenant_name' => $tenant->name,

            'lease_label' => $lease->reference
                ?? 'Lease #'.$lease->id,

            'building_label' => $lease->unit?->building?->name,

            'unit_label' => $lease->unit?->name,

            'performed_by_user_id' => $actor?->id,

            'performed_by_name' => $actor?->name
                ?? 'System',
        ]);
    }
}

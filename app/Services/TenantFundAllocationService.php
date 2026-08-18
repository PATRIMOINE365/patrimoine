<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Classifies received tenant money into held-fund accounts.
 *
 * This service is intentionally independent of the HTTP layer so the same
 * accounting operation can be used by:
 *
 * - normal tenant-fund API requests;
 * - historical Lease opening;
 * - future administrative workflows.
 */
class TenantFundAllocationService
{
    /**
     * Classify part of a Payment into a tenant-held fund.
     */
    public function allocate(
        Payment $payment,
        string $fundType,
        int $amount,
        string $transactionDate,
        ?string $reference = null,
        ?string $notes = null
    ): ?TenantFundTransaction {
        if ($amount === 0) {
            return null;
        }

        if ($amount < 0) {
            throw new RuntimeException(
                'Tenant fund allocation cannot be negative.'
            );
        }

        return DB::transaction(
            function () use (
                $payment,
                $fundType,
                $amount,
                $transactionDate,
                $reference,
                $notes
            ): TenantFundTransaction {
                $payment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if ($amount > $payment->allocatableAmount()) {
                    throw new RuntimeException(
                        'Tenant fund allocation exceeds available Payment funds.'
                    );
                }

                $category = match ($fundType) {
                    'rent_reserve' => 'reserve_funding',

                    'consumable_advance' => 'advance_funding',

                    'security_deposit' => 'deposit_funding',

                    default => throw new RuntimeException(
                        'Unsupported tenant fund type.'
                    ),
                };

                $account = TenantFundAccount::firstOrCreate(
                    [
                        'lease_id' => $payment->lease_id,
                        'type' => $fundType,
                    ],
                    [
                        'status' => 'active',
                    ]
                );

                $account->refresh();

                if ($account->status !== 'active') {
                    throw new RuntimeException(
                        'Tenant fund account is closed.'
                    );
                }

                return TenantFundTransaction::create([
                    'tenant_fund_account_id' => $account->id,
                    'payment_id' => $payment->id,
                    'direction' => 'credit',
                    'category' => $category,
                    'amount' => $amount,
                    'transaction_date' => $transactionDate,
                    'reference' => $reference ?? $payment->reference,
                    'notes' => $notes
                        ?? 'Classified from tenant Payment.',
                ]);
            }
        );
    }
}

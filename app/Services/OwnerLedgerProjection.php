<?php

namespace App\Services;

use App\Models\OwnerTransaction;
use LogicException;

/**
 * The ONE place a ledger movement is read into balances.
 *
 * Every owner transaction moves three figures — the Payout account, the
 * Deposit/Expense account, and the consolidated total — and the
 * invariant that holds them together is
 *
 *     total = payout + deposit
 *
 * Before V1.0.48 that invariant was a tautology: the payout balance was
 * DERIVED as total − deposit, so it could not catch anything, and three
 * different services each carried their own partial arithmetic. The
 * 2026-09-01 audit found where they disagreed: the report and receipt
 * services counted internal reserve transfers into the consolidated
 * total (finding 3), and the payout allocator subtracted deposit-side
 * expenses from payout-side money (finding 2).
 *
 * Here the three figures are computed INDEPENDENTLY — the pools from
 * the classification table below, the total from the plain
 * credit-minus-debit rule with internal transfers at zero — and
 * verified against each other on every read. A movement the table does
 * not know is an exception, never a silent zero: a category added
 * without deciding which pool it belongs to must fail the build, not
 * quietly disagree at a customer.
 *
 * The full table, for a movement of 100:
 *
 *   category            direction  funding      payout  deposit  total
 *   rent_entitlement    credit     —            +100    0        +100
 *   rent_entitlement    debit      —            −100    0        −100
 *   owner_deposit       credit     —            0       +100     +100
 *   owner_deposit       debit      —            0       −100     −100
 *   management_fee      debit      —            −100    0        −100
 *   management_fee      credit     —            +100    0        +100
 *   management_fee_vat  debit      —            −100    0        −100
 *   management_fee_vat  credit     —            +100    0        +100
 *   agent_commission    debit      —            −100    0        −100
 *   agent_commission    credit     —            +100    0        +100
 *   expense             debit      deposit/NULL 0       −100     −100
 *   expense             credit     deposit/NULL 0       +100     +100
 *   expense             debit      payout       −100    0        −100
 *   expense             credit     payout       +100    0        +100
 *   payout              debit      —            −100    0        −100
 *   payout              credit     —            +100    0        +100
 *   adjustment          credit     —            +100    0        +100
 *   adjustment          debit      —            −100    0        −100
 *   reserve_transfer    credit     —            −100    +100     0
 *   reserve_transfer    debit      —            +100    −100     0
 *
 * A NULL funding source is the historical default and means the
 * Deposit/Expense account. The deposit side may go negative — expense
 * debt beyond the deposits is money the owner owes the agency, and by
 * explicit business rule it is never silently taken from rent money.
 */
class OwnerLedgerProjection
{
    /**
     * Every category the ledger may carry. The guard test compares this
     * list against what the application actually writes.
     */
    public const CATEGORIES = [
        'rent_entitlement',
        'owner_deposit',
        'management_fee',
        'management_fee_vat',
        'agent_commission',
        'expense',
        'payout',
        'adjustment',
        'reserve_transfer',
    ];

    /**
     * The three balances of one owner account, each computed on its own.
     *
     * @return array{payout: int, deposit: int, total: int}
     */
    public function balancesFor(int $accountId): array
    {
        return $this->balances($accountId, null);
    }

    /**
     * The three balances as they stood at a ledger boundary — everything
     * up to and including that row. This is the receipt's question: what
     * the account held when a payout was made, expressed as the row the
     * payout stopped at.
     *
     * @return array{payout: int, deposit: int, total: int}
     */
    public function balancesThrough(
        int $accountId,
        int $throughMovementId
    ): array {
        if ($throughMovementId <= 0) {
            return ['payout' => 0, 'deposit' => 0, 'total' => 0];
        }

        return $this->balances($accountId, $throughMovementId);
    }

    /**
     * Classify one movement into its signed effect on the two pools.
     *
     * @return array{payout: int, deposit: int}
     *
     * @throws LogicException for a movement the table does not know —
     *         loudly, because a silent zero here is exactly how three
     *         services came to disagree about the same ledger.
     */
    public function classify(
        string $category,
        string $direction,
        ?string $fundingSource,
        int $amount
    ): array {
        $signed = match ($direction) {
            'credit' => $amount,
            'debit' => -$amount,
            default => throw new LogicException(
                "Owner ledger direction [{$direction}] is not classified."
            ),
        };

        return match ($category) {
            'rent_entitlement',
            'management_fee',
            'management_fee_vat',
            'agent_commission',
            'payout',
            'adjustment' => ['payout' => $signed, 'deposit' => 0],

            'owner_deposit' => ['payout' => 0, 'deposit' => $signed],

            'expense' => $this->classifyExpense(
                $fundingSource,
                $signed
            ),

            /*
             * An internal movement between the two pools: credit is INTO
             * the Deposit/Expense account, debit back OUT to Payout. The
             * total is untouched by construction.
             */
            'reserve_transfer' => [
                'payout' => -$signed,
                'deposit' => $signed,
            ],

            default => throw new LogicException(
                "Owner ledger category [{$category}] is not classified."
                .' Add it to OwnerLedgerProjection before writing it.'
            ),
        };
    }

    /**
     * The movement's signed effect on the consolidated total —
     * deliberately computed by its own rule, not from the pools, so the
     * invariant total = payout + deposit is a real cross-check.
     */
    public function totalEffect(
        string $category,
        string $direction,
        int $amount
    ): int {
        if ($category === 'reserve_transfer') {
            return 0;
        }

        return match ($direction) {
            'credit' => $amount,
            'debit' => -$amount,
            default => throw new LogicException(
                "Owner ledger direction [{$direction}] is not classified."
            ),
        };
    }

    /**
     * @return array{payout: int, deposit: int, total: int}
     */
    private function balances(
        int $accountId,
        ?int $throughMovementId
    ): array {
        $query = OwnerTransaction::query()
            ->where('owner_account_id', $accountId);

        if ($throughMovementId !== null) {
            $query->where('id', '<=', $throughMovementId);
        }

        $rows = $query
            ->selectRaw(
                'category, direction, funding_source,'
                .' SUM(amount) as total_amount'
            )
            ->groupBy('category', 'direction', 'funding_source')
            ->get();

        $payout = 0;
        $deposit = 0;
        $total = 0;

        foreach ($rows as $row) {
            $effect = $this->classify(
                (string) $row->category,
                (string) $row->direction,
                $row->funding_source,
                (int) $row->total_amount
            );

            $payout += $effect['payout'];
            $deposit += $effect['deposit'];

            $total += $this->totalEffect(
                (string) $row->category,
                (string) $row->direction,
                (int) $row->total_amount
            );
        }

        if ($payout + $deposit !== $total) {
            throw new LogicException(
                'Owner ledger projection is inconsistent for account '
                ."{$accountId}: payout {$payout} + deposit {$deposit}"
                ." != total {$total}. A movement is classified into the"
                .' pools differently than into the total.'
            );
        }

        return [
            'payout' => $payout,
            'deposit' => $deposit,
            'total' => $total,
        ];
    }

    /**
     * @return array{payout: int, deposit: int}
     */
    private function classifyExpense(
        ?string $fundingSource,
        int $signed
    ): array {
        return match ($fundingSource) {
            /*
             * NULL is the historical default and means the
             * Deposit/Expense account.
             */
            null,
            'deposit_account' => ['payout' => 0, 'deposit' => $signed],

            'payout_account' => ['payout' => $signed, 'deposit' => 0],

            default => throw new LogicException(
                "Owner expense funding source [{$fundingSource}]"
                .' is not classified.'
            ),
        };
    }
}

<?php

namespace App\Services;

use App\Models\OwnerPayoutAllocation;
use App\Models\OwnerTransaction;
use RuntimeException;

/**
 * Which portions of which funding sources are still available to pay
 * out — the replacement for the shortcut the 2026-09-01 audit called
 * finding 2.
 *
 * The old attribution subtracted EVERY historical non-payout debit from
 * the pooled historical credits, whatever pool either side belonged to.
 * A deposit-funded expense therefore ate rent money in the attribution
 * arithmetic while the validation, correctly, did not count it — and
 * the moment an owner's deposit side went negative, a perfectly valid
 * withdrawal failed in front of the customer. It failed CLOSED, which
 * is why the fault was visible at all; that safeguard stays.
 *
 * This engine replays the ledger in order and keeps the two pools
 * apart, portion by portion:
 *
 *  - rent, adjustments and other payout-side credits enter the PAYOUT
 *    pool; owner deposits enter the DEPOSIT pool — each as a portion
 *    remembering which ledger credit it came from;
 *  - a credit already attributed to an issued payout enters smaller by
 *    exactly that amount, so existing OwnerPayoutAllocation rows are
 *    counted once and can never be reassigned by later or backdated
 *    entries;
 *  - fees, commissions, VAT and payout-funded expenses consume the
 *    payout pool; deposit-funded expenses consume ONLY the deposit
 *    pool — going into deficit there rather than ever touching rent
 *    money, which is the business rule the audit confirmed;
 *  - reserve transfers move portions between the pools PRESERVING
 *    their origin, so a payout made from released deposit money can
 *    say so on its receipt;
 *  - reversal credits refill the pool their funding source names,
 *    restoring the position without duplicating money.
 *
 * What remains in the payout pool afterwards is exactly what a new
 * payout may consume, FIFO — and its total must equal the payout
 * balance OwnerLedgerProjection computes from the same rows. When the
 * two disagree the ledger holds something neither understands, and the
 * payout must refuse rather than guess.
 */
class OwnerPayoutAllocationEngine
{
    public function __construct(
        private readonly OwnerLedgerProjection $projection
    ) {}

    /**
     * The state of both pools after replaying the whole ledger.
     *
     * @return array{
     *     payout_portions: list<array{origin: int, amount: int}>,
     *     payout_available: int,
     *     payout_deficit: int,
     *     deposit_portions: list<array{origin: int, amount: int}>,
     *     deposit_available: int,
     *     deposit_deficit: int,
     * }
     */
    public function poolsFor(int $accountId): array
    {
        /*
         * What each issued payout consumed, keyed by the ledger DEBIT
         * that recorded it. Payout debits are written one per payout in
         * the order the payouts were made, so the nth debit belongs to
         * the nth payout — the same correspondence the frozen-statement
         * boundary uses. Replaying a payout debit then consumes exactly
         * the portions its recorded allocations name, which is what
         * "counted exactly once, never reassigned" means mechanically.
         */
        $allocationsByDebit = $this->allocationsByDebit($accountId);

        /*
         * FIFO order is the order the money became the owner's:
         * effective date first, recording order as the tiebreak — the
         * same ordering the previous allocator used, so existing
         * allocations replay against the same sequence they were
         * written against.
         */
        $movements = OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get([
                'id',
                'direction',
                'category',
                'funding_source',
                'amount',
            ]);

        $payout = ['portions' => [], 'deficit' => 0];
        $deposit = ['portions' => [], 'deficit' => 0];

        foreach ($movements as $movement) {
            $this->replay(
                $movement,
                $allocationsByDebit,
                $payout,
                $deposit
            );
        }

        return [
            'payout_portions' => array_values($payout['portions']),
            'payout_available' => $this->poolTotal($payout),
            'payout_deficit' => $payout['deficit'],
            'deposit_portions' => array_values($deposit['portions']),
            'deposit_available' => $this->poolTotal($deposit),
            'deposit_deficit' => $deposit['deficit'],
        ];
    }

    /**
     * Plan a new payout's attribution: which portions, FIFO, from the
     * payout pool.
     *
     * @return list<array{origin: int, amount: int}>
     *
     * @throws RuntimeException when the pool cannot cover the amount, or
     *         when the pool disagrees with the projection — both mean
     *         the ledger holds something the arithmetic does not
     *         understand, and refusing is safer than paying.
     */
    public function planAllocations(
        int $accountId,
        int $amount
    ): array {
        $pools = $this->poolsFor($accountId);

        /*
         * The independent cross-check: the pool replay and the balance
         * projection read the same rows by different rules and must
         * land on the same figure. A deficit anywhere means some past
         * movement overdrew a pool, and attribution arithmetic can no
         * longer be trusted for new money.
         */
        $projected = $this->projection
            ->balancesFor($accountId)['payout'];

        $agreesWithProjection =
            $pools['payout_available'] - $pools['payout_deficit']
            === $projected;

        if (
            ! $agreesWithProjection
            || $pools['payout_deficit'] > 0
        ) {
            throw new RuntimeException(
                __('business.owner.payout_allocation_failed')
            );
        }

        if ($amount > $pools['payout_available']) {
            throw new RuntimeException(
                __('business.owner.payout_allocation_failed')
            );
        }

        /*
         * Merged by origin: money that left for the reserve and came
         * back exists as several portions of one credit, and one payout
         * may consume more than one of them — but the allocation table
         * holds one row per (payout, credit) pair.
         */
        $planned = [];
        $remaining = $amount;

        foreach ($pools['payout_portions'] as $portion) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $portion['amount']);

            if ($take <= 0) {
                continue;
            }

            $planned[$portion['origin']] =
                ($planned[$portion['origin']] ?? 0) + $take;

            $remaining -= $take;
        }

        if ($remaining !== 0) {
            throw new RuntimeException(
                __('business.owner.payout_allocation_failed')
            );
        }

        $rows = [];

        foreach ($planned as $origin => $portionAmount) {
            $rows[] = [
                'origin' => (int) $origin,
                'amount' => $portionAmount,
            ];
        }

        return $rows;
    }

    /**
     * The recorded allocations of every issued payout, keyed by the
     * ledger debit row that recorded the payout leaving.
     *
     * @return array<int, list<array{origin: int, amount: int}>>
     */
    private function allocationsByDebit(int $accountId): array
    {
        $debitIds = OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->where('direction', 'debit')
            ->where('category', 'payout')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($debitIds === []) {
            return [];
        }

        $payoutIds = \App\Models\OwnerPayout::query()
            ->where('owner_account_id', $accountId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $allocations = OwnerPayoutAllocation::query()
            ->whereIn('owner_payout_id', $payoutIds)
            ->orderBy('id')
            ->get(['owner_payout_id', 'owner_transaction_id', 'amount'])
            ->groupBy('owner_payout_id');

        $map = [];

        foreach ($debitIds as $position => $debitId) {
            $payoutId = $payoutIds[$position] ?? null;

            $map[$debitId] = $payoutId === null
                ? []
                : ($allocations[$payoutId] ?? collect())
                    ->map(fn ($row): array => [
                        'origin' => (int) $row->owner_transaction_id,
                        'amount' => (int) $row->amount,
                    ])
                    ->all();
        }

        return $map;
    }

    /**
     * Apply one movement to the pools.
     *
     * @param  array<int, list<array{origin: int, amount: int}>>  $allocationsByDebit
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $payout
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $deposit
     */
    private function replay(
        OwnerTransaction $movement,
        array $allocationsByDebit,
        array &$payout,
        array &$deposit
    ): void {
        $category = (string) $movement->category;
        $direction = (string) $movement->direction;
        $amount = (int) $movement->amount;

        if ($category === 'reserve_transfer') {
            /*
             * credit = payout → deposit; debit = deposit → payout.
             * Portions move whole, keeping the origin they carried, so
             * released deposit money is still deposit money on the
             * receipt that eventually pays it out.
             */
            if ($direction === 'credit') {
                $this->move($payout, $deposit, $amount, $movement->id);
            } else {
                $this->move($deposit, $payout, $amount, $movement->id);
            }

            return;
        }

        if ($category === 'payout' && $direction === 'debit') {
            /*
             * An issued payout consumes exactly what its recorded
             * allocations name, portion by portion. History written by
             * the pre-V1.0.48 allocator may name a credit that never
             * entered THIS pool — it allocated against every credit
             * regardless of side — and the fallback for that is plain
             * FIFO, which reproduces the projection's arithmetic while
             * leaving the deposit pool alone.
             */
            $this->consumeForPayout(
                $payout,
                $allocationsByDebit[$movement->id] ?? [],
                $amount
            );

            return;
        }

        $effect = $this->projection->classify(
            $category,
            $direction,
            $movement->funding_source,
            $amount
        );

        /*
         * Every non-transfer movement touches exactly one pool.
         */
        $poolName = $effect['deposit'] !== 0 ? 'deposit' : 'payout';
        $signed = $effect[$poolName];

        if ($poolName === 'deposit') {
            $target = &$deposit;
        } else {
            $target = &$payout;
        }

        if ($signed > 0) {
            $this->credit($target, $signed, (int) $movement->id);
        } elseif ($signed < 0) {
            $this->consume($target, -$signed);
        }
    }

    /**
     * A payout debit leaves the payout pool: first the portions its
     * recorded allocations name, by origin; whatever those cannot cover
     * — a legacy attribution, or no allocations at all — leaves FIFO.
     *
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $pool
     * @param  list<array{origin: int, amount: int}>  $allocations
     */
    private function consumeForPayout(
        array &$pool,
        array $allocations,
        int $amount
    ): void {
        $consumed = 0;

        foreach ($allocations as $allocation) {
            $wanted = min(
                $allocation['amount'],
                $amount - $consumed
            );

            if ($wanted <= 0) {
                break;
            }

            $consumed += $this->consumeOrigin(
                $pool,
                $allocation['origin'],
                $wanted
            );
        }

        if ($amount > $consumed) {
            $this->consume($pool, $amount - $consumed);
        }
    }

    /**
     * Take up to $amount from portions carrying one specific origin.
     * Returns what was actually taken.
     *
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $pool
     */
    private function consumeOrigin(
        array &$pool,
        int $origin,
        int $amount
    ): int {
        $taken = 0;

        foreach ($pool['portions'] as $key => $portion) {
            if ($amount <= 0) {
                break;
            }

            if ($portion['origin'] !== $origin) {
                continue;
            }

            $take = min($amount, $portion['amount']);

            $amount -= $take;
            $taken += $take;

            if ($take === $portion['amount']) {
                unset($pool['portions'][$key]);
            } else {
                $pool['portions'][$key]['amount'] -= $take;
            }
        }

        return $taken;
    }

    /**
     * Money enters a pool: settle its deficit first, then keep the rest
     * as a portion remembering where it came from.
     *
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $pool
     */
    private function credit(
        array &$pool,
        int $amount,
        int $origin
    ): void {
        if ($amount <= 0) {
            return;
        }

        if ($pool['deficit'] > 0) {
            $settled = min($pool['deficit'], $amount);

            $pool['deficit'] -= $settled;
            $amount -= $settled;
        }

        if ($amount > 0) {
            $pool['portions'][] = [
                'origin' => $origin,
                'amount' => $amount,
            ];
        }
    }

    /**
     * Money leaves a pool, oldest portions first; anything beyond what
     * the pool holds becomes deficit rather than ever crossing into the
     * other pool.
     *
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $pool
     */
    private function consume(
        array &$pool,
        int $amount
    ): void {
        foreach ($pool['portions'] as $key => $portion) {
            if ($amount <= 0) {
                return;
            }

            $take = min($amount, $portion['amount']);

            $amount -= $take;

            if ($take === $portion['amount']) {
                unset($pool['portions'][$key]);
            } else {
                $pool['portions'][$key]['amount'] -= $take;
            }
        }

        if ($amount > 0) {
            $pool['deficit'] += $amount;
        }
    }

    /**
     * Move an amount between pools, portion by portion, origins kept.
     *
     * When the source cannot cover the whole movement — which validated
     * writes never allow, but eleven-year-old data might — the shortfall
     * becomes source deficit and enters the destination as a portion
     * carrying the transfer row itself, so money is neither created nor
     * lost.
     *
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $from
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $to
     */
    private function move(
        array &$from,
        array &$to,
        int $amount,
        int $transferId
    ): void {
        foreach ($from['portions'] as $key => $portion) {
            if ($amount <= 0) {
                return;
            }

            $take = min($amount, $portion['amount']);

            $amount -= $take;

            if ($take === $portion['amount']) {
                unset($from['portions'][$key]);
            } else {
                $from['portions'][$key]['amount'] -= $take;
            }

            $this->credit($to, $take, $portion['origin']);
        }

        if ($amount > 0) {
            $from['deficit'] += $amount;

            $this->credit($to, $amount, $transferId);
        }
    }

    /**
     * @param  array{portions: array<int, array{origin: int, amount: int}>, deficit: int}  $pool
     */
    private function poolTotal(array $pool): int
    {
        return array_sum(
            array_column($pool['portions'], 'amount')
        );
    }
}

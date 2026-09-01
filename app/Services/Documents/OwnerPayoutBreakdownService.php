<?php

namespace App\Services\Documents;

use App\Models\OwnerPayout;
use App\Models\OwnerTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Every movement that produced a payout figure.
 *
 * The receipt used to show the amount, a count of the ledger rows it
 * consumed, and the balance left. An owner holding it could not check the
 * number, so this produces the workings: a summary at the top, then the
 * transactions themselves, itemised — the rent collected, the fees and
 * the tax on them, and the expenses recorded against the property.
 *
 * V1.0.47: THE PERIOD IS A RECORDING PERIOD, NOT A DATE RANGE.
 *
 * It used to run from the day after the previous payout to this one, over
 * the live ledger, keyed on each movement's effective date. That made a
 * completed receipt a question about today's database, and Patrimoine
 * allows backdating: a payment recorded after a payout but dated before
 * it walked into that payout's receipt, and left the payout that actually
 * released it with nothing to show.
 *
 * It was also unable to describe two payouts made on the same day at all.
 * The second one's window ran from the day after the first to the same
 * date — an empty range — so its receipt showed an amount and no
 * workings.
 *
 * So the period is now everything RECORDED since the previous payout was
 * recorded, up to the moment this one was. That is the question a receipt
 * answers: what did the owner's account hold when this money was
 * released. A movement backdated afterwards belongs to the next payout,
 * whatever date it carries.
 *
 * The composition is frozen onto the payout when it is made and the
 * receipt renders from that. Composing it again is only ever a fallback,
 * for payouts made before this existed.
 *
 * Three rules keep it honest whatever the ledger holds:
 *
 *  - every movement in the period lands in exactly one of the three
 *    tables, so each table's total IS the summary line above it and a
 *    reader can add the tables up and arrive at the payout;
 *  - a category nobody thought of still lands in a table, under its own
 *    name, rather than being dropped;
 *  - brought forward is the account's own net position at the previous
 *    payout, so brought forward plus the tables IS available, and
 *    available less this payout IS carried forward. The arithmetic
 *    closes by construction rather than by agreement with another
 *    service.
 *
 * A receipt and an owner STATEMENT covering "the same period" may now
 * legitimately differ, and should: a statement answers what happened
 * between two dates, a receipt answers what justified one payment.
 */
class OwnerPayoutBreakdownService
{
    /**
     * Debit categories that are fees, tax or other deductions rather than
     * money spent on the property.
     */
    private const DEDUCTION_CATEGORIES = [
        'management_fee',
        'management_fee_vat',
        'agent_commission',
        'adjustment',
        'reserve_transfer',
    ];

    /**
     * The statement this payout was made against.
     *
     * The frozen one, where there is one. A payout made before payouts
     * were frozen has none, and composing it again is the best that can
     * be done for it.
     *
     * @return array<string, mixed>|null
     */
    public function forPayout(OwnerPayout $payout): ?array
    {
        $frozen = $payout->statement;

        if (is_array($frozen) && $frozen !== []) {
            return $frozen;
        }

        return $this->compose($payout);
    }

    /**
     * Work out the composition from the ledger as it stands.
     *
     * Called once, when the payout is made. Calling it later answers a
     * different question than it did then, which is the whole reason the
     * answer is written down.
     *
     * @return array<string, mixed>|null
     */
    public function compose(OwnerPayout $payout): ?array
    {
        $payout->loadMissing('ownerAccount.party');

        $account = $payout->ownerAccount;
        $owner = $account?->party;

        if ($account === null || $owner === null) {
            return null;
        }

        /*
         * The moment this payout was recorded is the edge of what it
         * could possibly have been made against.
         */
        $recordedAt = $payout->created_at
            ? CarbonImmutable::parse($payout->created_at)
            : CarbonImmutable::parse($payout->payout_date)->endOfDay();

        $previous = $this->previousPayout($payout);

        /*
         * The last ledger row that existed when this payout was made,
         * and the one the payout before it stopped at. Everything
         * between the two is what this payout was made against.
         */
        $through = $this->throughMovementId($account->id, $recordedAt, $payout);

        $previousThrough = $this->boundaryOf($previous, $account->id);

        $movements = $this->movements(
            $account->id,
            $previousThrough,
            $through
        );

        $received = $this->received($movements);
        $deductions = $this->deductions($movements);
        $expenses = $this->expenses($movements);

        $broughtForward = $this->netPositionThrough(
            $account->id,
            $previousThrough
        );

        $available =
            $broughtForward
            + $received['total']
            - $deductions['total']
            - $expenses['total'];

        /*
         * Another payout inside a recording window cannot happen: each
         * payout closes its own window at the moment it is recorded. The
         * line is kept so a reconstructed statement for something odd in
         * the history still adds up on the page rather than silently
         * not.
         */
        $otherPayouts = $movements
            ->where('direction', 'debit')
            ->where('category', 'payout')
            ->sum('amount')
            - (int) $payout->amount;

        return [
            /*
             * The label an owner reads is the DATE of the payout they
             * last collected — the event they remember. The boundary the
             * statement was actually selected on is the ledger row it
             * stopped at, which is a fact about the database and not
             * something to print on a receipt.
             */
            'from' => $previous?->payout_date?->toDateString(),

            /*
             * Kept so the next payout knows exactly where this one
             * stopped, without having to work it out from timestamps
             * that may since have become ambiguous.
             */
            'through_movement_id' => $through,
            'to' => CarbonImmutable::parse($payout->payout_date)->toDateString(),
            'has_previous_payout' => $previous !== null,

            'brought_forward' => $broughtForward,
            'received_total' => $received['total'],
            'deductions_total' => $deductions['total'],
            'expenses_total' => $expenses['total'],
            'available' => $available,
            'amount' => (int) $payout->amount,
            'other_payouts' => (int) $otherPayouts,
            'carried_forward' => $available - (int) $payout->amount - (int) $otherPayouts,

            'received' => $received['rows'],
            'deductions' => $deductions['rows'],
            'expenses' => $expenses['rows'],
        ];
    }

    /**
     * The last ledger row on this account that existed at a moment.
     *
     * Ledger ids are monotonic, so this is the whole of "what had been
     * recorded by then" expressed as one number.
     */
    private function throughMovementId(
        int $accountId,
        CarbonImmutable $recordedAt,
        ?OwnerPayout $payout = null
    ): int {
        $byTime = (int) OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->where('created_at', '<=', $recordedAt)
            ->max('id');

        /*
         * A payout always includes the debit that records it leaving.
         *
         * Timestamps are stored to the second, and two payouts entered in
         * the same second — a seeded account, an import, somebody quick —
         * read as one moment. The first would then claim every row up to
         * that second and leave the second payout with an empty window and
         * a receipt showing an amount and nothing else, which is the very
         * fault this release exists to remove.
         *
         * So the boundary is never earlier than the payout's own ledger
         * debit. Payout debits are written one per payout, in the order
         * the payouts were made, so the nth payout owns the nth debit.
         */
        $ownDebit = $payout === null
            ? 0
            : $this->ownDebitId($payout, $accountId);

        /*
         * Where the debit is known it IS the boundary, not merely a floor
         * for it. It is the last owner-ledger row the payout writes, so
         * everything up to it is what the payout was made against and
         * everything after it belongs to whatever comes next. Taking the
         * later of the two would let a payout entered in the same second
         * as the next one swallow that one's money as well.
         */
        return $ownDebit > 0
            ? $ownDebit
            : $byTime;
    }

    /**
     * The ledger debit that recorded this payout leaving the account.
     */
    private function ownDebitId(
        OwnerPayout $payout,
        int $accountId
    ): int {
        $position = OwnerPayout::query()
            ->where('owner_account_id', $accountId)
            ->where(
                fn ($query) => $query
                    ->where('created_at', '<', $payout->created_at)
                    ->orWhere(
                        fn ($same) => $same
                            ->where('created_at', '=', $payout->created_at)
                            ->where('id', '<=', $payout->id)
                    )
            )
            ->count();

        if ($position < 1) {
            return 0;
        }

        return (int) OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->where('direction', 'debit')
            ->where('category', 'payout')
            ->orderBy('id')
            ->skip($position - 1)
            ->take(1)
            ->pluck('id')
            ->first();
    }

    /**
     * Where a payout stopped.
     *
     * Its own frozen statement says so. One made before payouts were
     * frozen has to be reconstructed from when it was recorded, which is
     * the best evidence left.
     */
    private function boundaryOf(
        ?OwnerPayout $payout,
        int $accountId
    ): int {
        if ($payout === null) {
            return 0;
        }

        $frozen = $payout->statement;

        if (is_array($frozen) && isset($frozen['through_movement_id'])) {
            return (int) $frozen['through_movement_id'];
        }

        $recordedAt = $payout->created_at
            ? CarbonImmutable::parse($payout->created_at)
            : CarbonImmutable::parse($payout->payout_date)->endOfDay();

        return $this->throughMovementId($accountId, $recordedAt, $payout);
    }

    /**
     * What the account was worth when the previous payout stopped.
     *
     * Everything up to and including that row, netted — which includes
     * the previous payout's own debit, so the figure is what was left
     * after the owner last collected.
     */
    private function netPositionThrough(
        int $accountId,
        int $throughId
    ): int {
        if ($throughId <= 0) {
            return 0;
        }

        $credits = OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->where('direction', 'credit')
            ->where('id', '<=', $throughId)
            ->sum('amount');

        $debits = OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->where('direction', 'debit')
            ->where('id', '<=', $throughId)
            ->sum('amount');

        return (int) $credits - (int) $debits;
    }

    /**
     * Every movement recorded in the period, with what it takes to
     * describe one.
     *
     * Bounded by the ledger rows themselves, not by the dates they
     * carry. A rent payment for May entered in September belongs to
     * whatever payout was next after September.
     *
     * @return Collection<int, OwnerTransaction>
     */
    private function movements(
        int $accountId,
        int $after,
        int $through
    ): Collection {
        $query = OwnerTransaction::query()
            ->where('owner_account_id', $accountId)
            ->with([
                'building:id,name',
                'unit:id,name',
                /*
                 * The invoice carries the period the rent was for, which
                 * is what makes a rent row mean something: "Flat 4B, 1 to
                 * 31 July" rather than an amount on a date.
                 */
                'invoice:id,invoice_number,period_start,period_end',
                'ownerExpenseBill:id,bill_number',
            ])
            ->where('id', '>', $after)
            ->where('id', '<=', $through);

        return $query
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Money in.
     *
     * @param  Collection<int, OwnerTransaction>  $movements
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    private function received(Collection $movements): array
    {
        return $this->table(
            $movements->where('direction', 'credit'),
            fn (OwnerTransaction $movement): array => match ($movement->category) {
                'rent_entitlement' => $this->period($movement, null),
                'owner_deposit' => $this->dated($movement, 'deposit'),
                'adjustment' => $this->dated($movement, 'adjustment_in'),
                'reserve_transfer' => $this->dated($movement, 'reserve_in'),
                default => $this->dated($movement, null),
            }
        );
    }

    /**
     * Fees, the tax on them, and anything else taken off that was not
     * money spent on the property.
     *
     * @param  Collection<int, OwnerTransaction>  $movements
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    private function deductions(Collection $movements): array
    {
        return $this->table(
            $movements
                ->where('direction', 'debit')
                ->whereIn('category', self::DEDUCTION_CATEGORIES),
            fn (OwnerTransaction $movement): array => match ($movement->category) {
                'management_fee' => $this->period($movement, 'fee'),
                'management_fee_vat' => $this->period($movement, 'vat'),
                'agent_commission' => $this->period($movement, 'commission'),
                'adjustment' => $this->dated($movement, 'adjustment_out'),
                'reserve_transfer' => $this->dated($movement, 'reserve_out'),
                default => $this->dated($movement, null),
            }
        );
    }

    /**
     * Money spent on the property.
     *
     * Everything debited that is not a payout and not one of the
     * deductions above, so a category added later lands here rather than
     * vanishing out of the arithmetic.
     *
     * @param  Collection<int, OwnerTransaction>  $movements
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    private function expenses(Collection $movements): array
    {
        return $this->table(
            $movements
                ->where('direction', 'debit')
                ->reject(
                    fn (OwnerTransaction $movement): bool =>
                        $movement->category === 'payout'
                        || in_array(
                            $movement->category,
                            self::DEDUCTION_CATEGORIES,
                            true
                        )
                ),
            fn (OwnerTransaction $movement): array => [
                'label' => null,
                'text' => $this->expenseDescription($movement),
                'place' => $movement->building?->name,
                'date' => $movement->transaction_date->toDateString(),
                'from' => null,
                'to' => null,
            ]
        );
    }

    /**
     * Number the rows and total them.
     *
     * @param  Collection<int, OwnerTransaction>  $movements
     * @param  callable(OwnerTransaction): array<string, mixed>  $describe
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    private function table(
        Collection $movements,
        callable $describe
    ): array {
        $rows = [];

        $total = 0;

        foreach ($movements->values() as $index => $movement) {
            $rows[] = $describe($movement) + [
                'number' => $index + 1,
                'amount' => (int) $movement->amount,
            ];

            $total += (int) $movement->amount;
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * A row that names a place and the period it covers.
     *
     * @return array<string, mixed>
     */
    private function period(
        OwnerTransaction $movement,
        ?string $label
    ): array {
        return [
            'label' => $label,
            'text' => null,
            'place' => $movement->unit?->name
                ?? $movement->building?->name,
            'from' => $movement->invoice?->period_start?->toDateString(),
            'to' => $movement->invoice?->period_end?->toDateString(),
            /*
             * Without an invoice behind it there is no period to show, so
             * the row falls back to the date the money moved.
             */
            'date' => $movement->invoice === null
                ? $movement->transaction_date->toDateString()
                : null,
        ];
    }

    /**
     * A row that names a place and a single date.
     *
     * @return array<string, mixed>
     */
    private function dated(
        OwnerTransaction $movement,
        ?string $label
    ): array {
        return [
            'label' => $label,
            'text' => $label === null
                ? $movement->category
                : null,
            'place' => $movement->unit?->name
                ?? $movement->building?->name,
            'from' => null,
            'to' => null,
            'date' => $movement->transaction_date->toDateString(),
        ];
    }

    /**
     * What an expense row should call itself.
     *
     * An owner-share expense records the description inside its note, in
     * a sentence this application writes itself:
     *
     *     Allocated owner share of expense: Roof repair
     *
     * The prefix is ours, not the operator's, so removing it is reading
     * our own format rather than parsing prose — but it is only ever a
     * fallback: a bill payment names its bill, which is the better answer
     * where one exists.
     */
    private function expenseDescription(
        OwnerTransaction $movement
    ): ?string {
        if ($movement->ownerExpenseBill !== null) {
            return $movement->ownerExpenseBill->bill_number;
        }

        $notes = trim((string) $movement->notes);

        $prefix = 'Allocated owner share of expense: ';

        if (str_starts_with($notes, $prefix)) {
            return substr($notes, strlen($prefix));
        }

        return $notes === ''
            ? $movement->reference
            : $notes;
    }

    /**
     * The date of the payout before this one, if there is one.
     */
    /**
     * The payout recorded immediately before this one.
     *
     * By when it was RECORDED, not by the date it carries: two payouts
     * made on the same day are ordered by the moment each was entered,
     * which is the only ordering that can separate them. Falling back to
     * the id keeps that deterministic where a timestamp is missing.
     */
    private function previousPayout(
        OwnerPayout $payout
    ): ?OwnerPayout {
        return OwnerPayout::query()
            ->where('owner_account_id', $payout->owner_account_id)
            ->where('id', '!=', $payout->id)
            ->where(
                fn ($query) => $query
                    ->where('created_at', '<', $payout->created_at)
                    ->orWhere(
                        fn ($same) => $same
                            ->where('created_at', '=', $payout->created_at)
                            ->where('id', '<', $payout->id)
                    )
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}

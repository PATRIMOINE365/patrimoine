<?php

namespace App\Services\Documents;

use App\Models\OwnerPayout;
use App\Models\OwnerTransaction;
use App\Services\Reports\OwnerReportService;
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
 * The period is the one an owner asks about — everything since they last
 * collected — running from the day after the previous payout up to and
 * including this one. Where there is no previous payout it runs from the
 * beginning of the account.
 *
 * Three rules keep it honest whatever the ledger holds:
 *
 *  - every movement in the period lands in exactly one of the three
 *    tables, so each table's total IS the summary line above it and a
 *    reader can add the tables up and arrive at the payout;
 *  - a category nobody thought of still lands in a table, under its own
 *    name, rather than being dropped;
 *  - the totals are checked against OwnerReportService — the service
 *    behind the owner statement — so a receipt and a statement covering
 *    the same period cannot disagree.
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

    public function __construct(
        private OwnerReportService $reports
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function forPayout(OwnerPayout $payout): ?array
    {
        $payout->loadMissing('ownerAccount.party');

        $account = $payout->ownerAccount;
        $owner = $account?->party;

        if ($account === null || $owner === null) {
            return null;
        }

        $to = CarbonImmutable::parse($payout->payout_date);

        $previous = $this->previousPayoutDate($payout);

        /*
         * OwnerReportService treats `from` inclusively and computes the
         * opening balance from everything strictly before it, so starting
         * the day after the previous payout puts that payout — and every
         * movement up to it — into the brought-forward figure. It is the
         * same convention the statement drawer pre-fills with.
         */
        $from = $previous?->addDay();

        $movements = $this->movements($account->id, $from, $to);

        $received = $this->received($movements);
        $deductions = $this->deductions($movements);
        $expenses = $this->expenses($movements);

        $summary = $this->reports->generate(
            $owner,
            $from?->toDateString(),
            $to->toDateString()
        )['summary'];

        $broughtForward = (int) $summary['opening_balance'];

        $available =
            $broughtForward
            + $received['total']
            - $deductions['total']
            - $expenses['total'];

        /*
         * A second payout dated inside the window is not possible through
         * the application, but a backdated one is, and an owner reading a
         * receipt that does not add up would be right to distrust all of
         * it. Shown as its own line when it happens.
         */
        $otherPayouts =
            (int) $summary['payouts']
            - (int) $payout->amount;

        return [
            'from' => $from?->toDateString(),
            'to' => $to->toDateString(),
            'has_previous_payout' => $previous !== null,

            'brought_forward' => $broughtForward,
            'received_total' => $received['total'],
            'deductions_total' => $deductions['total'],
            'expenses_total' => $expenses['total'],
            'available' => $available,
            'amount' => (int) $payout->amount,
            'other_payouts' => $otherPayouts,
            'carried_forward' => (int) $summary['closing_balance'],

            'received' => $received['rows'],
            'deductions' => $deductions['rows'],
            'expenses' => $expenses['rows'],
        ];
    }

    /**
     * Every movement in the period, with what it takes to describe one.
     *
     * @return Collection<int, OwnerTransaction>
     */
    private function movements(
        int $accountId,
        ?CarbonImmutable $from,
        CarbonImmutable $to
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
            ->whereDate('transaction_date', '<=', $to);

        if ($from !== null) {
            $query->whereDate('transaction_date', '>=', $from);
        }

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
    private function previousPayoutDate(
        OwnerPayout $payout
    ): ?CarbonImmutable {
        $previous = OwnerPayout::query()
            ->where('owner_account_id', $payout->owner_account_id)
            ->where('id', '!=', $payout->id)
            ->where(
                fn ($query) => $query
                    ->whereDate('payout_date', '<', $payout->payout_date)
                    ->orWhere(
                        fn ($same) => $same
                            ->whereDate(
                                'payout_date',
                                '=',
                                $payout->payout_date
                            )
                            ->where('id', '<', $payout->id)
                    )
            )
            ->orderByDesc('payout_date')
            ->orderByDesc('id')
            ->first();

        return $previous === null
            ? null
            : CarbonImmutable::parse($previous->payout_date);
    }
}

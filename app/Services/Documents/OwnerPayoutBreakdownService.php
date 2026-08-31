<?php

namespace App\Services\Documents;

use App\Models\OwnerPayout;
use App\Services\Reports\OwnerReportService;
use Carbon\CarbonImmutable;

/**
 * How a payout figure was arrived at.
 *
 * The receipt used to show the amount, a count of the ledger rows it
 * consumed, and the balance left. An owner holding it could not check the
 * number: nothing said what came in, what was taken off, or over what
 * period. This produces the workings.
 *
 * The period is the one an owner actually asks about — everything since
 * they last collected — so it runs from the day after the previous payout
 * up to and including this one. Where there is no previous payout it runs
 * from the beginning of the account.
 *
 * The figures come from OwnerReportService, the same service behind the
 * owner statement, so a receipt and the statement covering the same period
 * can never disagree. Nothing here reads the ledger itself.
 *
 * Two rules keep the arithmetic honest whatever the ledger holds:
 *
 *  - the two totals are the period's own credit and debit sums, not the
 *    sum of the lines listed, so a category nobody thought of cannot
 *    quietly fall out of the reconciliation;
 *  - anything not itemised is shown as `other` rather than dropped.
 */
class OwnerPayoutBreakdownService
{
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

        $statement = $this->reports->generate(
            $owner,
            $from?->toDateString(),
            $to->toDateString()
        );

        $summary = $statement['summary'];

        $received = $this->lines(
            $summary,
            [
                'rent_entitlement',
                'owner_deposits',
                'adjustments_credit',
                'reserve_transfers_credit',
            ],
            (int) $summary['credits']
        );

        /*
         * The payout itself is a debit, and it is the thing being
         * explained rather than one of the deductions that led to it.
         */
        $deductedTotal =
            (int) $summary['debits']
            - (int) $summary['payouts'];

        $deducted = $this->lines(
            $summary,
            [
                'expenses',
                'management_fees',
                'management_fee_vat',
                'agent_commissions',
                'adjustments_debit',
                'reserve_transfers_debit',
            ],
            $deductedTotal
        );

        $broughtForward = (int) $summary['opening_balance'];

        $available =
            $broughtForward
            + (int) $summary['credits']
            - $deductedTotal;

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

            'received' => $received,
            'received_total' => (int) $summary['credits'],

            'deducted' => $deducted,
            'deducted_total' => $deductedTotal,

            'brought_forward' => $broughtForward,
            'available' => $available,
            'amount' => (int) $payout->amount,
            'other_payouts' => $otherPayouts,
            'carried_forward' => (int) $summary['closing_balance'],
        ];
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

    /**
     * Itemise the keys that carry a figure, then account for the rest.
     *
     * @param  array<string, mixed>  $summary
     * @param  array<int, string>  $keys
     * @return array<int, array{key: string, amount: int}>
     */
    private function lines(
        array $summary,
        array $keys,
        int $total
    ): array {
        $lines = [];

        $itemised = 0;

        foreach ($keys as $key) {
            $amount = (int) ($summary[$key] ?? 0);

            if ($amount === 0) {
                continue;
            }

            $lines[] = ['key' => $key, 'amount' => $amount];

            $itemised += $amount;
        }

        $remainder = $total - $itemised;

        if ($remainder !== 0) {
            $lines[] = ['key' => 'other', 'amount' => $remainder];
        }

        return $lines;
    }
}

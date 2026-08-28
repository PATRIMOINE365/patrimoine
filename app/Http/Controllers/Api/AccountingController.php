<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only API for the managing organisation's own accounting.
 *
 * Tenants and Owners each have a workspace showing money that belongs to
 * them. This endpoint answers the equivalent question for the organisation
 * running Patrimoine: what has it earned in management fees, and how much
 * VAT has it charged on those fees and therefore owes onward.
 *
 * Both figures are derived from the Owner ledger rather than stored again,
 * so they can never drift from what the Owners were actually charged.
 */
class AccountingController extends Controller
{
    /**
     * Categories this page reports on, in display order.
     *
     * @var array<int, string>
     */
    private const CATEGORIES = [
        'management_fee',
        'management_fee_vat',
    ];

    /**
     * Return fee income and VAT charged over an optional date window.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $totals = [];

        foreach (self::CATEGORIES as $category) {
            $totals[$category] = (int) $this
                ->scopedQuery($category, $from, $to)
                ->sum('amount');
        }

        /*
         * The transaction list is deliberately capped. This page reports on
         * a period; anyone needing the full history has the Financial
         * Journal, which is the authoritative record.
         */
        $transactions = $this
            ->scopedQuery(null, $from, $to)
            ->with([
                'lease.unit.building',
                'ownerAccount.party',
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (OwnerTransaction $transaction): array => [
                'id' => $transaction->id,
                'category' => $transaction->category,
                'amount' => (int) $transaction->amount,

                'transaction_date' => $transaction
                    ->transaction_date
                    ?->toDateString(),

                'owner_name' => $transaction
                    ->ownerAccount
                    ?->party
                    ?->name,

                'building_name' => $transaction
                    ->lease
                    ?->unit
                    ?->building
                    ?->name,

                'unit_name' => $transaction
                    ->lease
                    ?->unit
                    ?->name,

                'reference' => $transaction->reference,
            ])
            ->all();

        return response()->json([
            'period' => [
                'from' => $from,
                'to' => $to,
            ],

            'totals' => [
                'management_fee' =>
                    $totals['management_fee'],

                'management_fee_vat' =>
                    $totals['management_fee_vat'],

                /*
                 * What the organisation actually keeps: the VAT is
                 * collected on behalf of the tax authority and is not
                 * income.
                 */
                'net_fee_income' =>
                    $totals['management_fee'],

                'charged_to_owners' =>
                    $totals['management_fee']
                    + $totals['management_fee_vat'],
            ],

            'transactions' => $transactions,
        ]);
    }

    /**
     * Base query for organisation-side owner charges.
     */
    private function scopedQuery(
        ?string $category,
        ?string $from,
        ?string $to
    ) {
        $query = OwnerTransaction::query()
            ->whereIn(
                'category',
                $category === null
                    ? self::CATEGORIES
                    : [$category]
            );

        if ($from !== null) {
            $query->whereDate('transaction_date', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('transaction_date', '<=', $to);
        }

        return $query;
    }
}

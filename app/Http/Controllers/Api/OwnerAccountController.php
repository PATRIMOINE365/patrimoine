<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only API for owner financial accounts.
 *
 * OwnerAccount is the consolidated accounting identity used for:
 *
 * - collected rent entitlement;
 * - owner deposits;
 * - property expenses;
 * - management fees;
 * - agent commissions;
 * - owner payouts;
 * - manual accounting adjustments.
 *
 * Patrimoine 1.0 keeps one OwnerAccount per Owner Party, regardless of
 * how many Buildings that Party owns.
 */
class OwnerAccountController extends Controller
{
    /**
     * Return searchable OwnerAccounts for the Owner workspace.
     *
     * This endpoint is also intentionally suitable for lightweight owner
     * search fields used elsewhere in the application.
     *
     * Supported filters:
     *
     * - party_id
     * - search by owner name, legal name, phone or email
     */
    public function index(Request $request): JsonResponse
    {
        $query = OwnerAccount::query()
            ->with('party');

        if ($request->filled('party_id')) {
            $query->where(
                'party_id',
                (int) $request->input('party_id')
            );
        }

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->whereHas(
                'party',
                function ($query) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'legal_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        $accounts = $query
            ->orderByDesc('id')
            ->paginate(
                perPage: min(
                    max(
                        (int) $request->input(
                            'per_page',
                            25
                        ),
                        1
                    ),
                    100
                )
            );

        /*
         * Financial balances are intentionally calculated from the ledger.
         *
         * Patrimoine does not maintain a mutable cached balance on the
         * OwnerAccount record.
         */
        $accounts->getCollection()->transform(
            function (OwnerAccount $account): array {
                return [
                    'id' => $account->id,

                    'party_id' => $account->party_id,

                    'status' => $account->status,

                    'balance' => $account->balance(),

                    'credited_amount' => $account->creditedAmount(),

                    'debited_amount' => $account->debitedAmount(),

                    'property_count' => $account
                        ->party
                        ->buildingOwnerships()
                        ->count(),

                    'party' => $account->party,

                    'created_at' => $account->created_at,

                    'updated_at' => $account->updated_at,
                ];
            }
        );

        return response()->json(
            $accounts
        );
    }

    /**
     * Return the complete operational view of one OwnerAccount.
     *
     * The response includes:
     *
     * - owner identity;
     * - consolidated balance;
     * - credit/debit totals;
     * - Buildings owned;
     * - Units within those Buildings;
     * - recent owner ledger activity;
     * - owner payout history.
     *
     * Property visibility depends exclusively on Building ownership and not
     * on Lease activity. This ensures vacant properties remain available for
     * owner deposits, repairs and other financial operations.
     */
    public function show(
        Request $request,
        OwnerAccount $ownerAccount
    ): JsonResponse {
        $ownerAccount->load([
            'party.buildingOwnerships.building.units',
            'payouts',
        ]);

        $properties =
            $ownerAccount
                ->party
                ->buildingOwnerships
                ->map(
                    function ($ownership): array {
                        $building =
                            $ownership->building;

                        return [
                            'ownership_id' => $ownership->id,

                            'ownership_percentage' => $ownership->ownership_percentage,

                            'building' => [
                                'id' => $building->id,

                                'name' => $building->name,

                                'location' => $building->location,

                                'address' => $building->address,

                                'units' => $building
                                    ->units
                                    ->map(
                                        fn ($unit): array => [
                                            'id' => $unit->id,

                                            'name' => $unit->name,

                                            'description' => $unit->description,
                                        ]
                                    )
                                    ->values(),
                            ],
                        ];
                    }
                )
                ->values();

        /*
         * Ledger transactions are returned separately from the account
         * identity because this history may grow significantly over time.
         *
         * Pagination prevents an old OwnerAccount from producing an
         * excessively large API response.
         */
        $transactions =
            OwnerTransaction::query()
                ->where(
                    'owner_account_id',
                    $ownerAccount->id
                )
                ->with([
                    'building',
                    'unit',
                    'lease',
                    'invoice',
                ])
                ->orderByDesc(
                    'transaction_date'
                )
                ->orderByDesc(
                    'id'
                )
                ->paginate(
                    perPage: min(
                        max(
                            (int) $request->input(
                                'transactions_per_page',
                                25
                            ),
                            1
                        ),
                        100
                    ),
                    pageName: 'transactions_page'
                );

        /*
         * Owner deposit transactions represent actual incoming cash and
         * therefore have an official receipt.
         *
         * Other owner ledger categories are accounting movements rather than
         * receipts and intentionally do not expose a receipt endpoint.
         */
        $transactions
            ->getCollection()
            ->transform(
                function (OwnerTransaction $transaction): array {
                    return [
                        'id' => $transaction->id,

                        'direction' => $transaction->direction,

                        'category' => $transaction->category,

                        'amount' => $transaction->amount,

                        'transaction_date' => $transaction
                            ->transaction_date
                            ->toDateString(),

                        'payment_method' => $transaction->payment_method,

                        'deposit_purpose' => $transaction->deposit_purpose,

                        'collector_name' =>
                            $transaction->cash_receiver_name
                            ?? $transaction->collector_name,

                        'reference' => $transaction->reference,

                        'notes' => $transaction->notes,

                        'building' => $transaction->building,

                        'unit' => $transaction->unit,

                        'lease_id' => $transaction->lease_id,

                        'invoice_id' => $transaction->invoice_id,

                        'receipt_endpoint' => (
                            $transaction->direction === 'credit'
                            && $transaction->category === 'owner_deposit'
                        )
                                ? sprintf(
                                    '/api/owner-deposits/%d/receipt',
                                    $transaction->id
                                )
                                : null,

                        'created_at' => $transaction->created_at,
                    ];
                }
            );

        $payouts =
            $ownerAccount
                ->payouts
                ->sortByDesc('payout_date')
                ->values()
                ->map(
                    fn ($payout): array => [
                        'id' => $payout->id,

                        'amount' => $payout->amount,

                        'payout_date' => $payout
                            ->payout_date
                            ->toDateString(),

                        'payment_method' => $payout->payment_method,

                        'reference' => $payout->reference,

                        'notes' => $payout->notes,
                    ]
                );

        return response()->json([
            'id' => $ownerAccount->id,

            'party_id' => $ownerAccount->party_id,

            'status' => $ownerAccount->status,

            'balance' => $ownerAccount->balance(),

            'credited_amount' => $ownerAccount->creditedAmount(),

            'debited_amount' => $ownerAccount->debitedAmount(),

            'category_totals' => $ownerAccount->categoryTotals(),

            'party' => $ownerAccount->party,

            'properties' => $properties,

            'transactions' => $transactions,

            'payouts' => $payouts,

            'created_at' => $ownerAccount->created_at,

            'updated_at' => $ownerAccount->updated_at,
        ]);
    }
}

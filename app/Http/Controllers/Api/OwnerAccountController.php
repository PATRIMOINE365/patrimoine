<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only API for owner financial accounts.
 *
 * OwnerAccount is the accounting identity used for owner deposits,
 * expenses, payouts and other owner-ledger transactions.
 *
 * Patrimoine 1.0 keeps one consolidated OwnerAccount per Owner Party,
 * regardless of how many Buildings that Party owns.
 */
class OwnerAccountController extends Controller
{
    /**
     * Return OwnerAccounts together with their Owner Party.
     *
     * This endpoint is intentionally lightweight because it is also used by
     * browser-side owner search fields.
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
         * The balance is derived from the OwnerTransaction ledger rather
         * than stored as a mutable field on OwnerAccount.
         */
        $accounts->getCollection()->transform(
            function (OwnerAccount $account): array {
                return [
                    'id' =>
                        $account->id,

                    'party_id' =>
                        $account->party_id,

                    'status' =>
                        $account->status,

                    'balance' =>
                        $account->balance(),

                    'party' =>
                        $account->party,

                    'created_at' =>
                        $account->created_at,

                    'updated_at' =>
                        $account->updated_at,
                ];
            }
        );

        return response()->json(
            $accounts
        );
    }

    /**
     * Return one OwnerAccount with:
     *
     * - Owner Party;
     * - current consolidated balance;
     * - Buildings owned by the Party;
     * - Units belonging to those Buildings.
     *
     * Building ownership, rather than Lease activity, determines the property
     * list. This is important because an Owner may need to provide money for
     * repairs while a Building is completely vacant.
     */
    public function show(
        OwnerAccount $ownerAccount
    ): JsonResponse {
        $ownerAccount->load([
            'party.buildingOwnerships.building.units',
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
                            'ownership_id' =>
                                $ownership->id,

                            'ownership_percentage' =>
                                $ownership->ownership_percentage,

                            'building' => [
                                'id' =>
                                    $building->id,

                                'name' =>
                                    $building->name,

                                'location' =>
                                    $building->location,

                                'address' =>
                                    $building->address,

                                'units' =>
                                    $building
                                        ->units
                                        ->map(
                                            fn ($unit): array => [
                                                'id' =>
                                                    $unit->id,

                                                'name' =>
                                                    $unit->name,

                                                'description' =>
                                                    $unit->description,
                                            ]
                                        )
                                        ->values(),
                            ],
                        ];
                    }
                )
                ->values();

        return response()->json([
            'id' =>
                $ownerAccount->id,

            'party_id' =>
                $ownerAccount->party_id,

            'status' =>
                $ownerAccount->status,

            'balance' =>
                $ownerAccount->balance(),

            'party' =>
                $ownerAccount->party,

            /*
             * These are based solely on Building ownership.
             *
             * No Lease, tenant Payment or owner ledger history is required
             * for the Building to appear here.
             */
            'properties' =>
                $properties,

            'created_at' =>
                $ownerAccount->created_at,

            'updated_at' =>
                $ownerAccount->updated_at,
        ]);
    }
}

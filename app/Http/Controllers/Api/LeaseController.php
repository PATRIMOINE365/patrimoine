<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaseRequest;
use App\Http\Requests\UpdateLeaseRequest;
use App\Models\Lease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\LeaseInitializationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

    /**
     * REST API controller for Patrimoine Leases.
     *
     * Business validation remains in Form Requests and financial services so
     * this controller stays focused on HTTP/application orchestration.
     */
    class LeaseController extends Controller
    {
    /**
     * Return Leases with their Unit, Building, Tenant and Agent.
     *
     * Supported filters:
     * - status;
     * - Unit;
     * - Tenant;
     * - free-text search across Tenant, Unit and Building.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lease::query()
            ->with([
                'unit.building.ownerships.party',
                'tenant',
                'agent',
            ]);

        /*
        * Lease lifecycle filter.
        */
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        /*
        * Direct relationship filters remain useful for contextual screens
        * such as a Unit detail page or Tenant statement.
        */
        if ($request->filled('unit_id')) {
            $query->where(
                'unit_id',
                (int) $request->input('unit_id')
            );
        }

        if ($request->filled('tenant_id')) {
            $query->where(
                'tenant_id',
                (int) $request->input('tenant_id')
            );
        }

        /*
        * The main Lease screen needs one search box rather than separate
        * Tenant, Unit and Building searches.
        *
        * Search therefore traverses the principal Lease relationships.
        */
        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->whereHas(
                            'tenant',
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
                        )
                        ->orWhereHas(
                            'unit',
                            function ($query) use ($search): void {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereHas(
                                        'building',
                                        function ($query) use ($search): void {
                                            $query
                                                ->where(
                                                    'name',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'location',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'address',
                                                    'like',
                                                    "%{$search}%"
                                                );
                                        }
                                    );
                            }
                        );
                }
            );
        }

        return response()->json(
            $query
                ->orderByDesc('start_date')
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
                )
        );
    }
    /**
     * Create a Lease and initialize all operational records that should
     * already exist as of today.
     *
     * Backdated Active/Notice Leases immediately receive all missing rent
     * Invoices from their contractual start date through the current date.
     *
     * The entire operation is transactional so Patrimoine never leaves behind
     * a partially initialized Lease if billing initialization fails.
     */
/**
 * Create a Lease and reconstruct all records that should already exist.
 *
 * Contractual Lease attributes and historical opening-financial instructions
 * are deliberately separated because payment metadata does not belong to the
 * leases table.
 */

public function store(
    StoreLeaseRequest $request,
    LeaseInitializationService $initializer
): JsonResponse {
    $validated =
        $request->validated();

    /*
     * These fields describe an historical financial event rather than
     * contractual Lease attributes.
     */
    $openingFinancialData =
        Arr::only(
            $validated,
            [
                'advance_received',
                'advance_received_date',
                'advance_received_method',
                'advance_received_reference',
                'advance_received_collector',
            ]
        );

    $leaseAttributes =
        Arr::except(
            $validated,
            [
                'advance_received',
                'advance_received_date',
                'advance_received_method',
                'advance_received_reference',
                'advance_received_collector',
            ]
        );

    $lease = DB::transaction(
        function () use (
            $leaseAttributes,
            $openingFinancialData,
            $initializer
        ): Lease {
            $lease = Lease::create(
                $leaseAttributes
            );

            $initializer->initialize(
                lease: $lease,
                openingFinancialData:
                    $openingFinancialData
            );

            return $lease->refresh();
        }
    );

    return response()->json(
        data: $lease->load([
            'unit.building.ownerships.party',
            'tenant',
            'agent',
            'invoices',
            'payments.allocations.invoice',
            'tenantFundAccounts.transactions',
        ]),
        status: 201
    );
}



    /**
     * Return one Lease with its principal relationships.
     */
    public function show(Lease $lease): JsonResponse
    {
        return response()->json(
            $lease->load([
                'unit.building.ownerships.party',
                'tenant',
                'agent',
                'invoices',
                'payments',
                'tenantFundAccounts',
            ])
        );
    }


    /**
     * Update a Lease and initialize any historical operational records requested.
     *
     * This also allows older Leases that were created before opening-financial
     * reconstruction existed to be brought into the correct accounting state.
     */
    public function update(
        UpdateLeaseRequest $request,
        Lease $lease,
        LeaseInitializationService $initializer
    ): JsonResponse {
        $validated =
            $request->validated();

        /*
        * Historical payment instructions are deliberately kept separate
        * from contractual Lease attributes.
        */
        $openingFinancialData =
            Arr::only(
                $validated,
                [
                    'advance_received',
                    'advance_received_date',
                    'advance_received_method',
                    'advance_received_reference',
                    'advance_received_collector',
                ]
            );

        $leaseAttributes =
            Arr::except(
                $validated,
                [
                    'advance_received',
                    'advance_received_date',
                    'advance_received_method',
                    'advance_received_reference',
                    'advance_received_collector',
                ]
            );

        $lease = DB::transaction(
            function () use (
                $lease,
                $leaseAttributes,
                $openingFinancialData,
                $initializer
            ): Lease {
                /*
                * First persist the contractual state.
                */
                $lease->update(
                    $leaseAttributes
                );

                /*
                * Then reconstruct anything that should already exist:
                *
                * - missing historical Invoices;
                * - historical opening Advance Payment;
                * - Rent Reserve;
                * - FIFO rent settlement;
                * - Consumable Advance;
                * - owner entitlement;
                * - Managing Organisation fee;
                * - Agent commission.
                */
                $initializer->initialize(
                    lease: $lease->refresh(),
                    openingFinancialData:
                        $openingFinancialData
                );

                return $lease->refresh();
            }
        );

        return response()->json(
            $lease->load([
                'unit.building.ownerships.party',
                'tenant',
                'agent',
                'invoices',
                'payments.allocations.invoice',
                'tenantFundAccounts.transactions',
            ])
        );
    }

    /**
     * Delete only an unreferenced Lease.
     *
     * Database restrictions protect invoices, payments and financial
     * history from being removed accidentally.
     */
    public function destroy(Lease $lease): JsonResponse
    {
        $lease->delete();

        return response()->json(
            data: null,
            status: 204
        );
    }
}

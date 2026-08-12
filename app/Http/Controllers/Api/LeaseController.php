<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaseRequest;
use App\Http\Requests\UpdateLeaseRequest;
use App\Models\Lease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'unit.building',
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
     * Create a Lease.
     */
    public function store(
        StoreLeaseRequest $request
    ): JsonResponse {
        $lease = Lease::create(
            $request->validated()
        );

        return response()->json(
            data: $lease->load([
                'unit.building',
                'tenant',
                'agent',
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
                'unit.building',
                'tenant',
                'agent',
                'invoices',
                'payments',
                'tenantFundAccounts',
            ])
        );
    }

    /**
     * Update a Lease's contractual configuration and lifecycle state.
     */
    public function update(
        UpdateLeaseRequest $request,
        Lease $lease
    ): JsonResponse {
        $lease->update(
            $request->validated()
        );

        return response()->json(
            $lease->refresh()->load([
                'unit.building',
                'tenant',
                'agent',
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

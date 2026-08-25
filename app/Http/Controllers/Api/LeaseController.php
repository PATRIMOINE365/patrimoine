<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendLeaseRequest;
use App\Http\Requests\InitiateLeaseTerminationRequest;
use App\Http\Requests\StoreLeaseRequest;
use App\Http\Requests\UpdateLeaseRequest;
use App\Models\Lease;
use App\Services\ActivityLogService;
use App\Services\BusinessActivitySnapshotService;
use App\Services\BusinessRecordDeletionService;
use App\Services\LeaseDeletion\LeaseDeletionService;
use App\Services\LeaseDeletion\LeaseDeletionRestorationPlanService;
use App\Services\LeaseHistory\LeaseFinancialHistoryService;
use App\Services\LeaseInitializationService;
use App\Services\LeaseTermination\LeaseTerminationCancellationService;
use App\Services\LeaseTermination\LeaseTerminationCompletionService;
use App\Services\LeaseTermination\LeaseTerminationInitiationService;
use App\Services\LeaseTermination\LeaseTerminationSettlementService;
use App\Services\LeaseTerms\LeaseExtendService;
use App\Services\LeaseTerms\LeaseTermVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
         * V1.0.7 register filters: by building, by billing frequency, and
         * by contractual end date window (expiry management).
         */
        if ($request->filled('building_id')) {
            $query->whereHas(
                'unit',
                function ($query) use ($request): void {
                    $query->where(
                        'building_id',
                        (int) $request->input('building_id')
                    );
                }
            );
        }

        if ($request->filled('payment_frequency')) {
            $query->where(
                'payment_frequency',
                $request->string('payment_frequency')->toString()
            );
        }

        if ($request->filled('ending_before')) {
            $query
                ->whereNotNull('end_date')
                ->whereDate(
                    'end_date',
                    '<=',
                    $request->string('ending_before')->toString()
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

        $page = $query
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
            );

        /*
         * V1.0.7: portfolio-wide lifecycle counts for the register tiles.
         *
         * Deliberately unfiltered — the tiles describe the whole portfolio
         * while the paginated list below reflects the active filters.
         */
        $statusCounts = Lease::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return response()->json(
            array_merge(
                $page->toArray(),
                [
                    'status_counts' => [
                        'total' => (int) $statusCounts->sum(),
                        'active' => (int) ($statusCounts['active'] ?? 0),
                        'notice' => (int) ($statusCounts['notice'] ?? 0),
                        'draft' => (int) ($statusCounts['draft'] ?? 0),
                        'terminated' => (int) ($statusCounts['terminated'] ?? 0),
                    ],
                ]
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
        LeaseInitializationService $initializer,
        LeaseTermVersionService $termVersions,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
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

        /*
         * V1.0.8: the Cashier for a cash advance is always the logged-in
         * user; any client-supplied name is ignored.
         */
        if (
            ($openingFinancialData['advance_received_method'] ?? null)
                === 'cash'
        ) {
            $openingFinancialData['advance_received_collector'] =
                $request->user()->name;
        }

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
                $initializer,
                $termVersions,
                $activityLog,
                $request,
                $snapshots
            ): Lease {
                $lease = Lease::create(
                    $leaseAttributes
                );

                $initializer->initialize(
                    lease: $lease,
                    openingFinancialData: $openingFinancialData
                );

                /*
                 * Preserve the initial contractual state before any future
                 * Extend operation can supersede it.
                 */
                $termVersions->ensureBaseline(
                    $lease->refresh()
                );

                $lease = $lease
                    ->refresh()
                    ->load([
                        'unit.building',
                        'tenant',
                        'agent',
                    ]);

                /*
                 * Lease initialization can create financial records. Those are
                 * automatic consequences of this one human Lease action and are
                 * deliberately not logged here as separate human events.
                 */
                $activityLog->record(
                    action: 'lease.created',
                    request: $request,
                    entityType: 'lease',
                    entityId: $lease->id,
                    entityLabel: $snapshots->leaseLabel($lease),
                    snapshot: $snapshots->lease($lease),
                );

                return $lease;
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
     * Return the canonical chronological operational financial history
     * for one Lease.
     *
     * Historical presentation is owned by LeaseFinancialHistoryService so
     * the API and browser never reconstruct financial semantics separately.
     */
    public function financialHistory(
        Lease $lease,
        LeaseFinancialHistoryService $history
    ): JsonResponse {
        return response()->json(
            $history->generate(
                $lease
            )
        );
    }

    /**
     * Return the current V1.0.5 termination settlement position.
     *
     * This endpoint is intentionally read only.
     *
     * It does not apply Tenant funds, create deductions, issue refunds,
     * complete termination, or otherwise mutate financial state.
     */
    public function terminationSettlement(
        Lease $lease,
        LeaseTerminationSettlementService $settlement
    ): JsonResponse {
        try {
            return response()->json(
                $settlement->calculate(
                    $lease
                )
            );
        } catch (\RuntimeException $exception) {
            return response()->json(
                [
                    'message' =>
                        $exception->getMessage(),
                ],
                422
            );
        }
    }

    /**
     * Complete the controlled V1.0.5 Lease termination workflow.
     *
     * Completion is permitted only when the authoritative settlement
     * calculator reports that no financial blockers remain.
     */
    public function completeTermination(
        Request $request,
        Lease $lease,
        LeaseTerminationCompletionService $completion,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $lease->load([
            'unit.building',
            'tenant',
            'agent',
        ]);

        $before =
            $snapshots->lease(
                $lease
            );

        try {
            $lease =
                $completion->complete(
                    $lease
                );
        } catch (\RuntimeException $exception) {
            return response()->json(
                [
                    'message' =>
                        $exception->getMessage(),
                ],
                422
            );
        }

        $lease->load([
            'unit.building',
            'tenant',
            'agent',
        ]);

        $activityLog->record(
            action: 'lease.termination_completed',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel:
                $snapshots->leaseLabel(
                    $lease
                ),
            snapshot:
                $snapshots->lease(
                    $lease
                ),
            metadata: [
                'before' =>
                    $before,

                'termination_date' =>
                    $lease
                        ->termination_date
                        ?->toDateString(),

                'termination_completed_at' =>
                    $lease
                        ->termination_completed_at
                        ?->toIso8601String(),
            ],
        );

        return response()->json(
            $lease
        );
    }

    /**
     * Cancel an in-progress controlled Lease termination.
     *
     * Cancellation restores the Lease lifecycle state while preserving
     * immutable financial history created during termination initiation.
     */
    public function cancelTermination(
        Request $request,
        Lease $lease,
        LeaseTerminationCancellationService $cancellation,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $lease->load([
            'unit.building',
            'tenant',
            'agent',
        ]);

        $before =
            $snapshots->lease(
                $lease
            );

        try {
            $lease =
                $cancellation->cancel(
                    $lease
                );
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            return response()->json(
                [
                    'message' =>
                        $exception->getMessage(),
                ],
                422
            );
        }

        $lease->load([
            'unit.building',
            'tenant',
            'agent',
        ]);

        $activityLog->record(
            action: 'lease.termination_cancelled',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel:
                $snapshots->leaseLabel(
                    $lease
                ),
            snapshot:
                $snapshots->lease(
                    $lease
                ),
            metadata: [
                'before' =>
                    $before,
            ],
        );

        return response()->json(
            $lease
        );
    }

    /**
     * Return one Lease with its operational financial relationships.
     *
     * Tenant-fund workflows need authoritative Invoice settlement values.
     * These are calculated by the Invoice model from Payment allocations and
     * tenant-fund ledger transactions rather than recalculated in JavaScript.
     */
    public function show(Lease $lease): JsonResponse
    {
        $lease->load([
            'unit.building.ownerships.party',
            'tenant',
            'agent',
            'invoices',
            'payments',
            'tenantFundAccounts.transactions',
            'securityDepositDeductions',
            'securityDepositSettlement',
        ]);

        /*
        * Invoice settlement is derived from:
        *
        * - normal Payment allocations;
        * - Rent Reserve consumption;
        * - Consumable Advance consumption.
        *
        * Expose those calculated values explicitly so the browser can present
        * only legitimate outstanding rent obligations.
        */
        $lease->invoices->each(
            function ($invoice): void {
                $invoice->setAttribute(
                    'paid_amount',
                    $invoice->paidAmount()
                );

                $invoice->setAttribute(
                    'outstanding_amount',
                    $invoice->outstandingAmount()
                );
            }
        );

        return response()->json(
            $lease
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
        LeaseInitializationService $initializer,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
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

        /*
         * V1.0.8: the Cashier for a cash advance is always the logged-in
         * user; any client-supplied name is ignored.
         */
        if (
            ($openingFinancialData['advance_received_method'] ?? null)
                === 'cash'
        ) {
            $openingFinancialData['advance_received_collector'] =
                $request->user()->name;
        }

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
                $initializer,
                $activityLog,
                $request,
                $snapshots
            ): Lease {
                $beforeSnapshot =
                    $snapshots->lease(
                        $lease
                            ->fresh()
                            ->load([
                                'unit.building',
                                'tenant',
                                'agent',
                            ])
                    );

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
                    openingFinancialData: $openingFinancialData
                );

                $lease = $lease
                    ->refresh()
                    ->load([
                        'unit.building',
                        'tenant',
                        'agent',
                    ]);

                $afterSnapshot =
                    $snapshots->lease($lease);

                [$before, $after] =
                    $snapshots->changes(
                        $beforeSnapshot,
                        $afterSnapshot
                    );

                if ($before !== []) {
                    $activityLog->record(
                        action: 'lease.updated',
                        request: $request,
                        entityType: 'lease',
                        entityId: $lease->id,
                        entityLabel: $snapshots->leaseLabel($lease),
                        before: $before,
                        after: $after,
                    );
                }

                return $lease;
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
     * Extend the current contractual terms of one Lease.
     *
     * Extend deliberately has its own endpoint rather than reusing generic
     * Lease update because V1.0.5 permits only Rent Terms, Rent Increment
     * and Notes to change through this lifecycle action.
     */
    public function extend(
        ExtendLeaseRequest $request,
        Lease $lease,
        LeaseExtendService $extensions,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $beforeSnapshot =
            $snapshots->lease(
                $lease
                    ->fresh()
                    ->load([
                        'unit.building',
                        'tenant',
                        'agent',
                    ])
            );

        try {
            $lease =
                $extensions->extend(
                    lease: $lease,
                    validated: $request->validated(),
                    actor: $request->user()
                );
        } catch (\RuntimeException $exception) {
            return response()->json(
                [
                    'message' => $exception->getMessage(),
                ],
                422
            );
        }

        $lease =
            $lease->load([
                'unit.building',
                'tenant',
                'agent',
            ]);

        $afterSnapshot =
            $snapshots->lease(
                $lease
            );

        [$before, $after] =
            $snapshots->changes(
                $beforeSnapshot,
                $afterSnapshot
            );

        $activityLog->record(
            action: 'lease.extended',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel: $snapshots->leaseLabel(
                $lease
            ),
            before: $before,
            after: $after,
            metadata: [
                'effective_from' => $request->validated(
                    'effective_from'
                ),
            ],
        );

        return response()->json(
            $lease->load([
                'unit.building.ownerships.party',
                'tenant',
                'agent',
                'invoices',
                'payments.allocations.invoice',
                'tenantFundAccounts.transactions',
                'termVersions',
            ])
        );
    }

    /**
     * Initiate the controlled Lease termination workflow.
     *
     * This operation establishes termination intent only. Financial
     * settlement, final-period billing, documents and completion belong
     * to later controlled termination checkpoints.
     */
    public function initiateTermination(
        InitiateLeaseTerminationRequest $request,
        Lease $lease,
        LeaseTerminationInitiationService $terminations,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $validated = $request->validated();

        $before = $snapshots->lease(
            $lease->load([
                'unit.building',
                'tenant',
                'agent',
            ])
        );

        $lease = $terminations->initiate(
            lease: $lease,
            noticeDate: $validated['notice_date'],
            terminationDate: $validated['termination_date'],
            finalRentMode: $validated['final_rent_mode']
        );

        $lease->load([
            'unit.building',
            'tenant',
            'agent',
        ]);

        $activityLog->record(
            action: 'lease.termination_initiated',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel: $snapshots->leaseLabel($lease),
            snapshot: $snapshots->lease($lease),
            metadata: [
                'before' => $before,
                'notice_date' => $lease->termination_notice_date?->toDateString(),
                'termination_date' => $lease->termination_date?->toDateString(),
                'final_rent_mode' => $lease->termination_final_rent_mode,
            ],
        );

        return response()->json(
            $lease
        );
    }

    /**
     * Return the authoritative read-only destructive-deletion impact.
     *
     * This endpoint performs no mutation. The final DELETE request rebuilds
     * and revalidates the plan again under lock, so this preview is never
     * treated as authorization to delete stale state.
     */
    public function deletionImpact(
        Lease $lease,
        LeaseDeletionRestorationPlanService $plans
    ): JsonResponse {
        return response()->json(
            $plans->plan(
                $lease
            )
        );
    }

    /**
     * Permanently delete a Lease through the V1.0.5 controlled destructive
     * deletion workflow.
     */
    public function destroy(
        Request $request,
        Lease $lease,
        LeaseDeletionService $deletions
    ): JsonResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
            'confirmation' => [
                'required',
                'string',
            ],
            'current_password' => [
                'required',
                'string',
            ],
        ]);

        $actor = $request->user();

        if (! $actor instanceof \App\Models\User) {
            abort(401);
        }

        $deletions->delete(
            lease: $lease,
            actor: $actor,
            request: $request,
            reason: $validated['reason'],
            confirmation: $validated['confirmation'],
            currentPassword: $validated['current_password'],
        );

        return response()->json(
            data: null,
            status: 204
        );
    }
}

<?php

namespace App\Services\LeaseDeletion;

use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Phase 10D3
 *
 * Executes the operational database-deletion portion of destructive
 * Lease deletion.
 *
 * Responsibilities:
 * - rebuild and validate the deletion plan under lock;
 * - fail closed when the Lease cannot be deleted safely;
 * - delete known Lease-owned operational rows child-before-parent;
 * - preserve Journal and Activity Log history;
 * - participate in the caller's database transaction when one exists.
 *
 * This service deliberately does NOT:
 * - create Journal reversals;
 * - create the informational deletion Journal entry;
 * - create the Activity Log event;
 * - delete filesystem documents.
 *
 * Those operations are coordinated by the final destructive Lease
 * deletion workflow.
 */
class LeaseDeletionOperationalDeletionService
{
    public function __construct(
        private readonly LeaseDeletionRestorationPlanService $planService,
    ) {
    }

    /**
     * Execute FK-safe operational deletion.
     *
     * @param callable(string, array<int, int>): void|null $failureHook
     *        Test-only/failure-injection hook invoked immediately after
     *        each planned table deletion.
     *
     * @return array<string, mixed>
     */
    public function execute(
        Lease $lease,
        ?callable $failureHook = null,
    ): array {
        return DB::transaction(function () use ($lease, $failureHook) {
            /*
             * Lock the Lease first. This prevents the executor from relying
             * on a stale pre-confirmation snapshot while another request
             * modifies/deletes the same Lease.
             */
            $lockedLease = Lease::query()
                ->whereKey($lease->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedLease) {
                throw new RuntimeException(
                    sprintf(
                        'Lease %d no longer exists.',
                        (int) $lease->getKey()
                    )
                );
            }

            /*
             * Recalculate under lock. A plan generated for UI preview is
             * never authorization to perform destructive mutation.
             */
            $plan = $this->planService->plan($lockedLease);

            if (! ($plan['eligibility']['safe_to_execute'] ?? false)) {
                $reasons = array_values(array_filter(
                    $plan['eligibility']['blocking_reasons'] ?? []
                ));

                throw new RuntimeException(
                    $reasons === []
                        ? sprintf(
                            'Lease %d cannot be deleted safely.',
                            (int) $lockedLease->getKey()
                        )
                        : sprintf(
                            "Lease %d cannot be deleted safely: %s",
                            (int) $lockedLease->getKey(),
                            implode(' | ', $reasons)
                        )
                );
            }

            $steps = $plan['operational']['delete_in_order'] ?? null;

            if (! is_array($steps) || $steps === []) {
                throw new RuntimeException(
                    'Lease deletion plan contains no operational deletion steps.'
                );
            }

            $this->validatePlan(
                leaseId: (int) $lockedLease->getKey(),
                steps: $steps,
            );

            $deleted = [];

            foreach ($steps as $step) {
                $table = (string) $step['table'];

                $ids = array_values(array_unique(array_map(
                    'intval',
                    $step['ids'] ?? []
                )));

                if ($ids === []) {
                    $deleted[$table] = 0;

                    if ($failureHook) {
                        $failureHook($table, $ids);
                    }

                    continue;
                }

                /*
                 * All destructive targets are explicit IDs produced by the
                 * fail-closed impact/restoration planner. We intentionally
                 * avoid relationship cascades here so deletion ordering is
                 * visible and deterministic.
                 */
                $count = DB::table($table)
                    ->whereIn('id', $ids)
                    ->delete();

                if ($count !== count($ids)) {
                    throw new RuntimeException(
                        sprintf(
                            'Lease deletion expected to delete %d row(s) from %s but deleted %d.',
                            count($ids),
                            $table,
                            $count
                        )
                    );
                }

                $deleted[$table] = $count;

                if ($failureHook) {
                    $failureHook($table, $ids);
                }
            }

            return [
                'lease_id' => (int) $lockedLease->getKey(),
                'deleted' => $deleted,
                'preserved' => [
                    'journal_entries' => true,
                    'journal_lines' => true,
                    'activity_logs' => true,
                ],
                'documents' =>
                    $plan['documents'] ?? [
                        'action' => 'enumerate_before_execution',
                        'delete_operational_documents' => true,
                        'preserve_audit_evidence' => true,
                    ],
            ];
        });
    }

    /**
     * Reject malformed, reordered, duplicated or unexpectedly expanded
     * deletion plans before the first destructive statement executes.
     *
     * @param array<int, mixed> $steps
     */
    private function validatePlan(
        int $leaseId,
        array $steps,
    ): void {
        $expectedOrder = [
            'security_deposit_applications',
            'owner_payout_allocations',
            'withdrawal_receipts',
            'security_deposit_deductions',
            'security_deposit_settlements',
            'owner_transactions',
            'tenant_fund_transactions',
            'payment_allocations',
            'payments',
            'invoices',
            'tenant_fund_accounts',
            'rent_increments',
            'lease_term_versions',
            'leases',
        ];

        $actualOrder = [];

        foreach ($steps as $step) {
            if (! is_array($step)) {
                throw new RuntimeException(
                    'Lease deletion plan contains a malformed step.'
                );
            }

            $table = $step['table'] ?? null;
            $ids = $step['ids'] ?? null;

            if (
                ! is_string($table)
                || ! in_array($table, $expectedOrder, true)
                || ! is_array($ids)
            ) {
                throw new RuntimeException(
                    'Lease deletion plan contains an unsupported operational target.'
                );
            }

            $actualOrder[] = $table;

            foreach ($ids as $id) {
                if (
                    ! is_int($id)
                    && ! (is_string($id) && ctype_digit($id))
                ) {
                    throw new RuntimeException(
                        sprintf(
                            'Lease deletion plan contains an invalid ID for %s.',
                            $table
                        )
                    );
                }
            }
        }

        if ($actualOrder !== $expectedOrder) {
            throw new RuntimeException(
                'Lease deletion plan does not match the required FK-safe deletion order.'
            );
        }

        $leaseStep = $steps[array_key_last($steps)];

        $leaseIds = array_values(array_map(
            'intval',
            $leaseStep['ids'] ?? []
        ));

        if ($leaseIds !== [$leaseId]) {
            throw new RuntimeException(
                'Lease deletion plan does not terminate with the locked Lease.'
            );
        }
    }
}

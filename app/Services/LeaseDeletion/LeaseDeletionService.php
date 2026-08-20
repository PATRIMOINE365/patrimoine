<?php

namespace App\Services\LeaseDeletion;

use App\Models\Lease;
use App\Models\User;
use App\Services\Accounting\JournalInformationalEventService;
use App\Services\ActivityLogService;
use App\Services\BusinessActivitySnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LeaseDeletionService
{
    public function __construct(
        private readonly LeaseDeletionRestorationPlanService $plans,
        private readonly LeaseDeletionJournalReversalService $reversals,
        private readonly LeaseDeletionOperationalDeletionService $operational,
        private readonly JournalInformationalEventService $informational,
        private readonly ActivityLogService $activityLog,
        private readonly BusinessActivitySnapshotService $snapshots,
    ) {
    }

    /**
     * Execute one complete destructive Lease deletion.
     *
     * All database consequences deliberately share one outer transaction:
     *
     * 1. lock/reload Lease;
     * 2. re-evaluate deletion safety;
     * 3. create accounting reversals;
     * 4. remove Lease-owned operational rows;
     * 5. create permanent informational Journal event;
     * 6. create permanent frozen Activity Log event.
     *
     * Any failure rolls the complete database operation back.
     *
     * @return array<string, mixed>
     */
    public function delete(
        Lease $lease,
        User $actor,
        Request $request,
        string $reason,
        string $confirmation,
        string $currentPassword,
        ?callable $failureHook = null,
    ): array {
        $reason = trim($reason);
        $confirmation = trim($confirmation);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('validation.required', [
                    'attribute' => 'reason',
                ]),
            ]);
        }

        if ($confirmation !== 'DELETE') {
            throw ValidationException::withMessages([
                'confirmation' =>
                    __(
                        'api.deletion.lease_confirmation_invalid'
                    ),
            ]);
        }

        if (
            $currentPassword === ''
            || ! Hash::check($currentPassword, $actor->password)
        ) {
            throw ValidationException::withMessages([
                'current_password' =>
                    __('api.password.current_password_incorrect'),
            ]);
        }

        return DB::transaction(function () use (
            $lease,
            $actor,
            $request,
            $reason,
            $failureHook
        ): array {
            /*
             * Lock the root record before the final plan is calculated.
             * The lower-level executors independently re-check their own
             * invariants as an additional fail-closed boundary.
             */
            $locked = Lease::query()
                ->whereKey($lease->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $targetId = (int) $locked->getKey();

            $locked->load([
                'unit.building',
                'tenant',
                'agent',
            ]);

            $targetLabel =
                $this->snapshots->leaseLabel($locked);

            $leaseSnapshot =
                $this->snapshots->lease($locked);

            $plan =
                $this->plans->plan($locked);

            if (
                (
                    $plan['eligibility']
                        ['safe_to_execute']
                    ?? false
                ) !== true
            ) {
                $blockingReasons =
                    $plan['eligibility']
                        ['blocking_reasons']
                    ?? [];

                $cannotDeleteMessage =
                    __(
                        'api.deletion.lease_cannot_delete',
                        [
                            'id' =>
                                $targetId,
                        ]
                    );

                throw ValidationException::withMessages([
                    'lease' =>
                        $blockingReasons === []
                            ? $cannotDeleteMessage
                            : sprintf(
                                '%s %s',
                                $cannotDeleteMessage,
                                implode(
                                    ' | ',
                                    $blockingReasons
                                )
                            ),
                ]);
            }

            if ($failureHook !== null) {
                $failureHook(
                    'after_final_plan',
                    $locked,
                    $plan
                );
            }

            /*
             * Journal history survives the operational deletion.
             * Reversals therefore happen before source rows disappear.
             */
            $reversalResult =
                $this->reversals->execute(
                    lease: $locked,
                    reason: $reason,
                    actorUserId: (int) $actor->getKey(),
                );

            if ($failureHook !== null) {
                $failureHook(
                    'after_journal_reversals',
                    $locked,
                    $plan
                );
            }

            /*
             * Delete only the operational rows proven Lease-owned by the
             * restoration plan. Journal and Activity Log history are not
             * deleted by this executor.
             */
            $operationalResult =
                $this->operational->execute(
                    lease: $locked
                );

            if ($failureHook !== null) {
                $failureHook(
                    'after_operational_delete',
                    $locked,
                    $plan
                );
            }

            /*
             * The Lease row no longer exists at this point, so all permanent
             * history must use the frozen ID/label/snapshot captured above.
             */
            $informationalEntry =
                $this->informational->record(
                    transactionType: 'lease_deletion',
                    description:
                        "Destructive deletion of Lease {$targetId}: {$targetLabel}",
                    actorUserId: (int) $actor->getKey(),
                    sourceType: 'lease',
                    sourceId: $targetId,
                    snapshot: $leaseSnapshot,
                    metadata: [
                        'reason' => $reason,
                        'impact' => $plan,
                        'journal_reversals' =>
                            $reversalResult,
                        'operational_deletion' =>
                            $operationalResult,
                    ],
                );

            if ($failureHook !== null) {
                $failureHook(
                    'after_informational_journal',
                    $locked,
                    $plan
                );
            }

            $activity =
                $this->activityLog->record(
                    action: 'lease.deleted',
                    actor: $actor,
                    request: $request,
                    entityType: 'lease',
                    entityId: $targetId,
                    entityLabel: $targetLabel,
                    snapshot: $leaseSnapshot,
                    metadata: [
                        'reason' => $reason,
                        'impact' => $plan,
                        'journal_reversals' =>
                            $reversalResult,
                        'operational_deletion' =>
                            $operationalResult,
                        'informational_journal_entry_id' =>
                            $informationalEntry->id,
                        'informational_journal_number' =>
                            $informationalEntry->journal_number,
                    ],
                );

            if ($failureHook !== null) {
                $failureHook(
                    'after_activity_log',
                    $locked,
                    $plan
                );
            }

            return [
                'lease_id' => $targetId,
                'lease_label' => $targetLabel,
                'reason' => $reason,
                'journal_reversals' =>
                    $reversalResult,
                'operational_deletion' =>
                    $operationalResult,
                'informational_journal_entry_id' =>
                    $informationalEntry->id,
                'informational_journal_number' =>
                    $informationalEntry->journal_number,
                'activity_log_id' =>
                    $activity->id,
            ];
        });
    }
}

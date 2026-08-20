<?php

namespace App\Services\LeaseDeletion;

use App\Models\Lease;

/**
 * Phase 10C2
 *
 * Builds a deterministic, read-only restoration/reversal plan for
 * destructive Lease deletion.
 *
 * IMPORTANT:
 * - This service MUST NOT mutate operational state.
 * - This service MUST NOT delete anything.
 * - This service MUST NOT create Journal entries.
 * - This service MUST NOT reverse Journal entries.
 * - This service MUST fail closed.
 *
 * Execution belongs to a later Phase 10 service.
 */
class LeaseDeletionRestorationPlanService
{
    public function __construct(
        private readonly LeaseDeletionImpactService $impactService,
        private readonly LeaseDeletionMonetaryImpactService $monetaryService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function plan(Lease $lease): array
    {
        $impact = $this->impactService->calculate($lease);
        $monetary = $this->monetaryService->calculate($lease);

        $blockingReasons = $impact['blocking_reasons'] ?? [];

        foreach ($monetary['unknown'] ?? [] as $unknown) {
            $blockingReasons[] =
                $unknown['reason']
                ?? 'A monetary effect could not be classified safely.';
        }

        $journal = $impact['journal'] ?? [];

        $reversalCandidates = [];

        foreach ($journal['active_entries'] ?? [] as $entry) {
            $reversalCandidates[] = [
                'journal_entry_id' =>
                    isset($entry['id'])
                        ? (int) $entry['id']
                        : null,

                'journal_number' =>
                    $entry['journal_number'] ?? null,

                'source_type' =>
                    $entry['source_type'] ?? null,

                'source_id' =>
                    isset($entry['source_id'])
                        ? (int) $entry['source_id']
                        : null,

                'transaction_type' =>
                    $entry['transaction_type'] ?? null,

                'action' =>
                    'reverse_full_entry',

                'reason' =>
                    'Active Journal entry is exclusively attributable to a Lease-owned operational source.',
            ];
        }

        $alreadyNeutralized = [];

        foreach (
            $journal['already_reversed_entries'] ?? []
            as $entry
        ) {
            $alreadyNeutralized[] = [
                'journal_entry_id' =>
                    isset($entry['id'])
                        ? (int) $entry['id']
                        : null,

                'journal_number' =>
                    $entry['journal_number'] ?? null,

                'reversal_of_id' =>
                    isset($entry['reversal_of_id'])
                        ? (int) $entry['reversal_of_id']
                        : null,

                'reversed_by_id' =>
                    isset($entry['reversed_by_id'])
                        ? (int) $entry['reversed_by_id']
                        : null,

                'action' =>
                    'no_additional_reversal',
            ];
        }

        $openingBalanceActions = [];

        foreach (
            $journal['opening_balance_entries'] ?? []
            as $entry
        ) {
            $classification =
                $entry['classification']
                ?? 'opening_balance_requires_review';

            $action = match ($classification) {
                'lease_specific_tenant_fund_opening_balance',
                'lease_specific_receivable_opening_balance'
                    => 'neutralize_lease_specific_opening_effect',

                'consolidated_owner_opening_balance'
                    => 'blocked_requires_partial_owner_neutralization',

                default
                    => 'blocked_requires_review',
            };

            $openingBalanceActions[] = [
                'journal_entry_id' =>
                    isset($entry['id'])
                        ? (int) $entry['id']
                        : null,

                'journal_number' =>
                    $entry['journal_number'] ?? null,

                'source_type' =>
                    $entry['source_type'] ?? null,

                'source_id' =>
                    isset($entry['source_id'])
                        ? (int) $entry['source_id']
                        : null,

                'classification' =>
                    $classification,

                'action' =>
                    $action,
            ];

            if (
                in_array(
                    $action,
                    [
                        'blocked_requires_partial_owner_neutralization',
                        'blocked_requires_review',
                    ],
                    true
                )
            ) {
                $blockingReasons[] =
                    sprintf(
                        'Opening-balance Journal entry %s cannot yet be neutralized deterministically for Lease %d.',
                        $entry['journal_number']
                            ?? $entry['id']
                            ?? 'unknown',
                        $lease->id
                    );
            }
        }

        /*
         * Operational deletion ordering.
         *
         * Children appear before parents. This is a PLAN only; the eventual
         * executor must still use one database transaction and obey actual
         * FK constraints discovered at execution time.
         */
        $direct =
            $impact['direct_dependencies'] ?? [];

        $indirect =
            $impact['indirect_dependencies'] ?? [];

        /*
         * FK-safe child-before-parent order.
         *
         * Important restrictive relationships include:
         *
         * security_deposit_applications
         *     -> tenant_fund_transactions
         *     -> invoices
         *
         * owner_payout_allocations
         *     -> owner_transactions
         *
         * owner_transactions
         *     -> payment_allocations
         *     -> invoices
         *
         * tenant_fund_transactions may also reference operational payment /
         * invoice context and always belong to Tenant Fund Accounts.
         *
         * This remains only a plan. The destructive executor must revalidate
         * the graph under lock before mutation.
         */
        $operationalDeletion = [
            [
                'table' =>
                    'security_deposit_applications',

                'ids' =>
                    $direct[
                        'security_deposit_applications'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'owner_payout_allocations',

                'ids' =>
                    $indirect[
                        'owner_payout_allocations'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'withdrawal_receipts',

                'ids' =>
                    $direct[
                        'withdrawal_receipts'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'security_deposit_deductions',

                'ids' =>
                    $direct[
                        'security_deposit_deductions'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'security_deposit_settlements',

                'ids' =>
                    $direct[
                        'security_deposit_settlements'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'owner_transactions',

                'ids' =>
                    $direct[
                        'owner_transactions'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'tenant_fund_transactions',

                'ids' =>
                    $indirect[
                        'tenant_fund_transactions'
                    ]['ids']
                    ?? [],
            ],
            [
                'table' =>
                    'payment_allocations',

                'ids' =>
                    $indirect[
                        'payment_allocations'
                    ]['ids']
                    ?? [],
            ],
        ];

        foreach (
            [
                'payments',
                'invoices',
                'tenant_fund_accounts',
                'rent_increments',
                'lease_term_versions',
            ]
            as $table
        ) {
            $operationalDeletion[] = [
                'table' => $table,
                'ids' =>
                    $direct[$table]['ids']
                    ?? [],
            ];
        }

        $operationalDeletion[] = [
            'table' => 'leases',
            'ids' => [(int) $lease->id],
        ];

        $sharedPayouts =
            $indirect['owner_payout_allocations']['shared_payouts']
            ?? [];

        foreach ($sharedPayouts as $sharedPayout) {
            $blockingReasons[] =
                sprintf(
                    'Owner payout %s contains both Lease-owned and outside allocations and cannot be deleted as a Lease-exclusive operation.',
                    $sharedPayout['owner_payout_id']
                        ?? 'unknown'
                );
        }

        foreach (
            $journal['shared_entries'] ?? []
            as $entry
        ) {
            $blockingReasons[] =
                sprintf(
                    'Journal entry %s contains a shared financial effect.',
                    $entry['journal_number']
                        ?? $entry['id']
                        ?? 'unknown'
                );
        }

        foreach (
            $journal['unclassified_entries'] ?? []
            as $entry
        ) {
            $blockingReasons[] =
                sprintf(
                    'Journal entry %s is not structurally attributable to this Lease.',
                    $entry['journal_number']
                        ?? $entry['id']
                        ?? 'unknown'
                );
        }

        $blockingReasons =
            array_values(
                array_unique(
                    array_filter(
                        $blockingReasons
                    )
                )
            );

        $fullyClassified =
            ($impact['safe_to_delete'] ?? false)
            && ($monetary['fully_classified'] ?? false)
            && $blockingReasons === [];

        return [
            'lease' =>
                $impact['lease'] ?? [
                    'id' => (int) $lease->id,
                ],

            'eligibility' => [
                'safe_to_execute' =>
                    $fullyClassified,

                'blocking_reasons' =>
                    $blockingReasons,
            ],

            'accounting' => [
                'runtime_enabled' =>
                    $impact['accounting']['runtime_enabled']
                    ?? false,

                'reversal_candidates' =>
                    $reversalCandidates,

                'already_neutralized' =>
                    $alreadyNeutralized,

                'opening_balance_actions' =>
                    $openingBalanceActions,

                'shared_entries' =>
                    $journal['shared_entries']
                    ?? [],

                'unclassified_entries' =>
                    $journal['unclassified_entries']
                    ?? [],
            ],

            'operational' => [
                'delete_in_order' =>
                    $operationalDeletion,

                'monetary_restoration' =>
                    $monetary,
            ],

            /*
             * Journal and Activity Log are historical evidence and are
             * deliberately excluded from operational deletion.
             */
            'preserve' => [
                'journal_entries' => true,
                'journal_lines' => true,
                'activity_logs' => true,
            ],

            /*
             * Document deletion is deliberately not guessed here.
             * A later execution step must enumerate concrete storage paths
             * from the operational records before mutation begins.
             */
            'documents' => [
                'action' =>
                    'enumerate_before_execution',

                'delete_operational_documents' =>
                    true,

                'preserve_audit_evidence' =>
                    true,
            ],

            'execution_contract' => [
                'atomic' => true,
                'mandatory_reason' => true,
                'typed_confirmation' => 'DELETE',
                'password_reverification' => true,
                'journal_reversals_before_operational_delete' =>
                    true,
                'informational_deletion_journal_event' =>
                    true,
                'activity_log_snapshot' =>
                    true,
                'failure_injection_required' =>
                    true,
            ],
        ];
    }
}

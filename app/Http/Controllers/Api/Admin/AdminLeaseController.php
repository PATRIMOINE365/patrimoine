<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Payment;
use App\Services\ActivityLogService;
use App\Services\BusinessActivitySnapshotService;
use App\Support\OrganisationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Correct a customer's Lease from the platform console.
 *
 * Customers cannot freely rewrite a Lease, because posted Invoices and
 * Journal entries are derived from its terms. Support still has to fix
 * genuine mistakes -- a rent typed wrong, a start date a month out -- so
 * this endpoint allows it, and is explicit about the consequences.
 *
 * Fields are split in two:
 *
 * - SAFE_FIELDS have no posted financial consequence and save freely.
 * - POSTED_IMPACT_FIELDS are what Invoices and Journal entries were
 *   derived from. Changing one does NOT retrospectively rewrite those
 *   records, so the endpoint reports exactly what will stop matching and
 *   refuses to proceed without a written reason.
 *
 * Nothing here re-posts accounting. Correcting the contract and
 * correcting the ledger are deliberately separate acts.
 */
class AdminLeaseController extends Controller
{
    /**
     * Editable with no posted consequence.
     */
    private const SAFE_FIELDS = [
        'notes',
        'agent_id',
        'end_date',
        'next_rent_increment_date',
        'rent_increment_type',
        'rent_increment_value',
    ];

    /**
     * Editable, but posted records were derived from these.
     */
    private const POSTED_IMPACT_FIELDS = [
        'start_date',
        'rent_amount',
        'payment_frequency',
        'due_day',
        'vat_rate',
        'management_fee_type',
        'management_fee_value',
        'security_deposit_amount',
        'advance_payment_amount',
        'rent_reserve_amount',
    ];

    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly BusinessActivitySnapshotService $snapshots,
    ) {
    }

    /**
     * Show one Lease with its editable fields and what is already posted
     * against it.
     */
    public function show(
        Request $request,
        int $organisationId,
        int $lease
    ): JsonResponse {
        /*
         * V1.0.51: resolved through customers() like every other console
         * endpoint, so the platform organisation answers 404 here too.
         */
        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($organisationId);

        $payload = OrganisationContext::runAs(
            (int) $organisation->id,
            function () use ($lease): array {
                $model = Lease::query()
                    ->with(['unit.building', 'tenant', 'agent'])
                    ->findOrFail($lease);

                return [
                    'lease' => $this->present($model),
                    'posted' => $this->postedFootprint($model),

                    /*
                     * V1.0.51: the agents this organisation actually has,
                     * so the drawer offers a choice instead of a box for
                     * a number that could belong to anybody.
                     */
                    'agents' => Party::query()
                        ->whereHas(
                            'roles',
                            fn ($query) => $query->where('role', 'agent')
                        )
                        ->orderBy('name')
                        ->get(['id', 'name'])
                        ->map(fn (Party $party): array => [
                            'id' => (int) $party->id,
                            'name' => (string) $party->name,
                        ])
                        ->all(),
                ];
            }
        );

        /*
         * V1.0.51: reading a customer's lease is written to the
         * platform's own trail. A read leaves no mark on the customer's
         * side — their log would fill with support's every glance — but
         * the platform must be able to say who looked at what.
         */
        $this->activityLog->record(
            action: 'platform.lease_viewed',
            actor: $request->user(),
            request: $request,
            entityType: 'lease',
            entityId: $lease,
            entityLabel: (string) ($payload['lease']['tenant_name'] ?? ('#'.$lease)),
            metadata: [
                'customer_organisation_id' => (int) $organisation->id,
                'customer_organisation' => $organisation->name,
            ],
            organisationId: (int) $request->user()->organisation_id,
        );

        return response()->json($payload + [
            'organisation' => [
                'id' => (int) $organisation->id,
                'name' => $organisation->name,
            ],

            'fields' => [
                'safe' => self::SAFE_FIELDS,
                'posted_impact' => self::POSTED_IMPACT_FIELDS,
            ],
        ]);
    }

    /**
     * Apply a correction on the customer's behalf.
     */
    public function update(
        Request $request,
        int $organisationId,
        int $lease
    ): JsonResponse {
        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($organisationId);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],

            'notes' => ['nullable', 'string'],
            'agent_id' => ['nullable', 'integer'],
            'end_date' => ['nullable', 'date'],
            'next_rent_increment_date' => ['nullable', 'date'],
            'rent_increment_type' => [
                'nullable',
                Rule::in(['none', 'percentage', 'fixed']),
            ],
            'rent_increment_value' => ['nullable', 'numeric', 'min:0'],

            'start_date' => ['nullable', 'date'],
            'rent_amount' => ['nullable', 'integer', 'min:0'],
            'payment_frequency' => [
                'nullable',
                Rule::in(['monthly', 'quarterly', 'semi_annual', 'annual']),
            ],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'management_fee_type' => [
                'nullable',
                Rule::in(['none', 'percentage', 'fixed']),
            ],
            'management_fee_value' => ['nullable', 'numeric', 'min:0'],
            'security_deposit_amount' => ['nullable', 'integer', 'min:0'],
            'advance_payment_amount' => ['nullable', 'integer', 'min:0'],
            'rent_reserve_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        /*
         * Refuse a request that carries no editable field at all.
         *
         * Without this an unparseable or empty body validates cleanly --
         * every field is nullable -- and the endpoint answers 200 with an
         * empty change set, which reads to the operator as "saved". A tool
         * that corrects money must never look like it worked when nothing
         * reached it.
         */
        $submittedFields = array_intersect(
            array_keys($validated),
            array_merge(self::SAFE_FIELDS, self::POSTED_IMPACT_FIELDS)
        );

        if ($submittedFields === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lease' => 'No lease fields were submitted.',
            ]);
        }

        $actor = $request->user();

        $result = OrganisationContext::runAs(
            (int) $organisation->id,
            function () use (
                $lease,
                $validated,
                $reason,
                $actor,
                $request,
                $organisation
            ): array {
                return DB::transaction(function () use (
                    $lease,
                    $validated,
                    $reason,
                    $actor,
                    $request,
                    $organisation
                ): array {
                    $model = Lease::query()
                        ->whereKey($lease)
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                     * V1.0.51: an agent must be one of THIS organisation's
                     * agents. The field was validated as a bare integer,
                     * so a party from another organisation was saved
                     * (and then could not be resolved), and a number
                     * nobody owns hit the foreign key and answered 500.
                     * Checked inside runAs so the tenant scope does the
                     * boundary work.
                     */
                    if (
                        array_key_exists('agent_id', $validated)
                        && $validated['agent_id'] !== null
                    ) {
                        $isAgentHere = Party::query()
                            ->whereKey((int) $validated['agent_id'])
                            ->whereHas(
                                'roles',
                                fn ($query) => $query->where('role', 'agent')
                            )
                            ->exists();

                        if (! $isAgentHere) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'agent_id' => [
                                    __('api.validation.agent_role_required'),
                                ],
                            ]);
                        }
                    }

                    $changes = $this->intendedChanges($model, $validated);

                    if ($changes === []) {
                        return [
                            'changed' => [],
                            'lease' => $this->present(
                                $model->fresh()->load([
                                    'unit.building', 'tenant', 'agent',
                                ])
                            ),
                        ];
                    }

                    $risky = array_values(array_intersect(
                        array_keys($changes),
                        self::POSTED_IMPACT_FIELDS
                    ));

                    /*
                     * A field that posted money was derived from may not
                     * be rewritten anonymously.
                     */
                    if ($risky !== [] && $reason === '') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'reason' => sprintf(
                                'A reason is required: %s already produced posted records and changing them will not rewrite those records.',
                                implode(', ', $risky)
                            ),
                        ]);
                    }

                    $before = $this->snapshots->lease(
                        $model->fresh()->load([
                            'unit.building', 'tenant', 'agent',
                        ])
                    );

                    $footprint = $this->postedFootprint($model);

                    $model->update(
                        array_map(
                            fn (array $change) => $change['to'],
                            $changes
                        )
                    );

                    $fresh = $model->fresh()->load([
                        'unit.building', 'tenant', 'agent',
                    ]);

                    $after = $this->snapshots->lease($fresh);

                    $this->activityLog->record(
                        action: 'platform.lease.corrected',
                        actor: $actor,
                        request: $request,
                        entityType: 'lease',
                        entityId: (int) $lease,
                        entityLabel: $this->snapshots->leaseLabel($fresh),
                        before: $before,
                        after: $after,
                        metadata: [
                            'reason' => $reason,
                            'changed_fields' => array_keys($changes),
                            'posted_impact_fields' => $risky,
                            'changes' => $changes,

                            /*
                             * Record what was already posted at the moment
                             * of the correction, so the audit trail shows
                             * what stopped matching and by how much.
                             */
                            'posted_at_correction' => $footprint,

                            'performed_by_platform_staff' => true,
                            'organisation_id' => (int) $organisation->id,
                        ],
                        organisationId: (int) $organisation->id,
                    );

                    /*
                     * V1.0.51: the same correction in the platform's OWN
                     * trail. It was written to the customer's log only,
                     * so the console page that says "every console
                     * action" never listed a lease correction.
                     */
                    $this->activityLog->record(
                        action: 'platform.lease.corrected',
                        actor: $actor,
                        request: $request,
                        entityType: 'lease',
                        entityId: (int) $lease,
                        entityLabel: $this->snapshots->leaseLabel($fresh),
                        metadata: [
                            'customer_organisation_id' => (int) $organisation->id,
                            'customer_organisation' => $organisation->name,
                            'reason' => $reason,
                            'changed_fields' => array_keys($changes),
                            'posted_impact_fields' => $risky,
                        ],
                        organisationId: (int) $actor->organisation_id,
                    );

                    return [
                        'changed' => array_keys($changes),
                        'posted_impact' => $risky,
                        'lease' => $this->present($fresh),
                    ];
                });
            }
        );

        return response()->json($result);
    }

    /**
     * Compare submitted values against stored ones.
     *
     * @param array<string, mixed> $validated
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function intendedChanges(
        Lease $lease,
        array $validated
    ): array {
        $editable = array_merge(
            self::SAFE_FIELDS,
            self::POSTED_IMPACT_FIELDS
        );

        $changes = [];

        foreach ($editable as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $current = $lease->getAttribute($field);

            $currentComparable = $current instanceof \DateTimeInterface
                ? $current->format('Y-m-d')
                : $current;

            $submitted = $validated[$field];

            if ((string) $currentComparable === (string) $submitted) {
                continue;
            }

            $changes[$field] = [
                'from' => $currentComparable,
                'to' => $submitted,
            ];
        }

        return $changes;
    }

    /**
     * What already exists downstream of this Lease's terms.
     *
     * @return array<string, mixed>
     */
    private function postedFootprint(Lease $lease): array
    {
        $invoices = Invoice::query()
            ->where('lease_id', $lease->id)
            ->get();

        $payments = Payment::query()
            ->where('lease_id', $lease->id)
            ->count();

        $journalEntries = JournalEntry::query()
            ->where('snapshot', 'like', '%"lease_id":'.$lease->id.'%')
            ->count();

        return [
            'invoices' => $invoices->count(),
            'invoiced_total' => (int) $invoices->sum('total_amount'),
            'payments' => $payments,
            'journal_entries' => $journalEntries,

            /*
             * Once anything is posted, a change to the terms those
             * postings came from cannot be applied retrospectively.
             */
            'has_posted_records' =>
                $invoices->isNotEmpty()
                || $payments > 0
                || $journalEntries > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Lease $lease): array
    {
        return [
            'id' => $lease->id,
            'status' => $lease->status,
            'tenant_name' => $lease->tenant?->name,
            'agent_id' => $lease->agent_id,
            'agent_name' => $lease->agent?->name,
            'unit_name' => $lease->unit?->name,
            'building_name' => $lease->unit?->building?->name,
            'start_date' => $lease->start_date?->toDateString(),
            'end_date' => $lease->end_date?->toDateString(),
            'rent_amount' => (int) $lease->rent_amount,
            'payment_frequency' => $lease->payment_frequency,
            'due_day' => $lease->due_day,
            'vat_rate' => $lease->vat_rate,
            'management_fee_type' => $lease->management_fee_type,
            'management_fee_value' => $lease->management_fee_value,
            'security_deposit_amount' => (int) $lease->security_deposit_amount,
            'advance_payment_amount' => (int) $lease->advance_payment_amount,
            'rent_reserve_amount' => (int) $lease->rent_reserve_amount,
            'rent_increment_type' => $lease->rent_increment_type,
            'rent_increment_value' => $lease->rent_increment_value,
            'next_rent_increment_date' => $lease->next_rent_increment_date?->toDateString(),
            'notes' => $lease->notes,
        ];
    }
}

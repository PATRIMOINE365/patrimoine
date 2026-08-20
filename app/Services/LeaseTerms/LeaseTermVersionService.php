<?php

namespace App\Services\LeaseTerms;

use App\Models\Lease;
use App\Models\LeaseTermVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Owns creation of immutable contractual Lease term versions.
 *
 * Phase 8A uses this service only for baseline creation. Later Phase 8
 * lifecycle work will reuse the same append-only mechanism.
 */
class LeaseTermVersionService
{
    /**
     * Contractual state preserved by each version.
     *
     * Lifecycle fields such as status and termination_notice_date are
     * intentionally not contractual-term version fields.
     *
     * @var list<string>
     */
    public const TERM_FIELDS = [
        'unit_id',
        'tenant_id',
        'agent_id',
        'start_date',
        'end_date',
        'rent_amount',
        'payment_frequency',
        'due_day',
        'vat_rate',
        'proration_amount',
        'security_deposit_amount',
        'advance_payment_amount',
        'rent_reserve_amount',
        'rent_increment_type',
        'rent_increment_value',
        'next_rent_increment_date',
        'management_fee_type',
        'management_fee_value',
        'agent_commission_amount',
        'notes',
    ];

    /**
     * Ensure one baseline exists for a newly created Lease.
     */
    public function ensureBaseline(
        Lease $lease
    ): LeaseTermVersion {
        return DB::transaction(
            function () use ($lease): LeaseTermVersion {
                $existing =
                    LeaseTermVersion::query()
                        ->where(
                            'lease_id',
                            $lease->id
                        )
                        ->where(
                            'version_number',
                            1
                        )
                        ->first();

                if ($existing !== null) {
                    return $existing;
                }

                return LeaseTermVersion::create([
                    'lease_id' => $lease->id,

                    'version_number' => 1,

                    'event_type' => 'baseline',

                    'effective_from' => $lease
                        ->start_date
                        ->toDateString(),

                    'effective_to' => null,

                    'terms' => $this->snapshot(
                        $lease
                    ),

                    /*
                     * Baseline represents the initial contractual state,
                     * not a later extension action.
                     *
                     * Actor attribution already exists in Activity Log for
                     * Lease creation. Later extension versions will carry
                     * their own actor explicitly.
                     */
                    'created_by_user_id' => null,
                ]);
            }
        );
    }

    /**
     * Append a future Lease term version.
     *
     * Phase 8A intentionally exposes the primitive but no HTTP/domain Extend
     * workflow calls it yet.
     *
     * @param  array<string, mixed>  $terms
     */
    public function append(
        Lease $lease,
        string $eventType,
        string $effectiveFrom,
        array $terms,
        ?User $actor = null
    ): LeaseTermVersion {
        return DB::transaction(
            function () use (
                $lease,
                $eventType,
                $effectiveFrom,
                $terms,
                $actor
            ): LeaseTermVersion {
                $latest =
                    LeaseTermVersion::query()
                        ->where(
                            'lease_id',
                            $lease->id
                        )
                        ->lockForUpdate()
                        ->orderByDesc(
                            'version_number'
                        )
                        ->first();

                if ($latest === null) {
                    throw new RuntimeException(
                        'Lease term baseline is missing.'
                    );
                }

                /*
                 * Closing the previous version is deliberately deferred to
                 * the controlled Extend service in Phase 8B because that
                 * service owns effective-date validation.
                 *
                 * Phase 8A only establishes append-only sequencing.
                 */
                return LeaseTermVersion::create([
                    'lease_id' => $lease->id,

                    'version_number' => $latest->version_number + 1,

                    'event_type' => $eventType,

                    'effective_from' => $effectiveFrom,

                    'effective_to' => null,

                    'terms' => $terms,

                    'created_by_user_id' => $actor?->id,
                ]);
            }
        );
    }

    /**
     * Produce a stable JSON-safe snapshot of current contractual terms.
     *
     * @return array<string, mixed>
     */
    public function snapshot(
        Lease $lease
    ): array {
        $lease->refresh();

        $snapshot = [];

        foreach (
            self::TERM_FIELDS as $field
        ) {
            $value =
                $lease->getAttribute(
                    $field
                );

            if (
                $value instanceof \DateTimeInterface
            ) {
                $value =
                    $value->format(
                        'Y-m-d'
                    );
            }

            $snapshot[$field] =
                $value;
        }

        return $snapshot;
    }
}

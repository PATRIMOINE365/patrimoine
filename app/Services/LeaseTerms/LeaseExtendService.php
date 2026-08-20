<?php

namespace App\Services\LeaseTerms;

use App\Models\Lease;
use App\Models\LeaseTermVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Own the controlled V1.0.5 Lease Extend mutation.
 *
 * This service deliberately exposes only the fields permitted by the
 * frozen Extend contract. Generic Lease editing must not be recreated
 * through this path.
 */
class LeaseExtendService
{
    /**
     * Fields that Extend may change in this backend step.
     *
     * Proration override is intentionally excluded.
     *
     * @var list<string>
     */
    public const EDITABLE_FIELDS = [
        'end_date',
        'rent_amount',
        'payment_frequency',
        'due_day',
        'vat_rate',
        'rent_increment_type',
        'rent_increment_value',
        'next_rent_increment_date',
        'notes',
    ];

    public function __construct(
        private readonly LeaseTermVersionService $termVersions
    ) {
    }

    /**
     * Apply a controlled extension while preserving the superseded
     * contractual state.
     *
     * @param  array<string, mixed>  $validated
     */
    public function extend(
        Lease $lease,
        array $validated,
        ?User $actor = null
    ): Lease {
        return DB::transaction(
            function () use (
                $lease,
                $validated,
                $actor
            ): Lease {
                /*
                 * Lock the Lease itself so two Extend operations cannot race
                 * while deriving historical effective-date boundaries.
                 */
                $lease =
                    Lease::query()
                        ->whereKey($lease->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->termVersions->ensureBaseline(
                    $lease
                );

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

                $effectiveFrom =
                    CarbonImmutable::parse(
                        $validated['effective_from']
                    )->startOfDay();

                $latestEffectiveFrom =
                    CarbonImmutable::parse(
                        $latest->effective_from
                    )->startOfDay();

                /*
                 * Each appended version must begin after the version it
                 * supersedes. This makes repeated extensions deterministic
                 * and prevents zero/negative historical periods.
                 */
                if (
                    $effectiveFrom->lte(
                        $latestEffectiveFrom
                    )
                ) {
                    throw new RuntimeException(
                        'Extension effective date must be after the current term version effective date.'
                    );
                }

                $attributes =
                    Arr::only(
                        $validated,
                        self::EDITABLE_FIELDS
                    );

                /*
                 * No-op extensions create misleading contractual history.
                 */
                $changed = false;

                foreach (
                    $attributes as $field => $value
                ) {
                    $current =
                        $lease->getAttribute(
                            $field
                        );

                    if (
                        $current instanceof \DateTimeInterface
                    ) {
                        $current =
                            $current->format(
                                'Y-m-d'
                            );
                    }

                    if (
                        $value instanceof \DateTimeInterface
                    ) {
                        $value =
                            $value->format(
                                'Y-m-d'
                            );
                    }

                    if ((string) $current !== (string) $value) {
                        $changed = true;
                        break;
                    }
                }

                if (! $changed) {
                    throw new RuntimeException(
                        'The extension does not change any permitted Lease terms.'
                    );
                }

                /*
                 * Lease remains the operational source for current terms.
                 */
                $lease->update(
                    $attributes
                );

                /*
                 * Snapshot AFTER applying the new current terms. Identity and
                 * other non-editable contractual fields remain present in the
                 * immutable version exactly as required by Phase 8A.
                 */
                $newTerms =
                    $this->termVersions->snapshot(
                        $lease
                    );

                /*
                 * LeaseTermVersion is intentionally immutable through Eloquent,
                 * therefore close the superseded version with a direct query
                 * update inside this controlled service only.
                 */
                DB::table('lease_term_versions')
                    ->where(
                        'id',
                        $latest->id
                    )
                    ->update([
                        'effective_to' =>
                            $effectiveFrom
                                ->subDay()
                                ->toDateString(),
                    ]);

                $this->termVersions->append(
                    lease: $lease,
                    eventType: 'extension',
                    effectiveFrom:
                        $effectiveFrom
                            ->toDateString(),
                    terms: $newTerms,
                    actor: $actor
                );

                return $lease->refresh();
            }
        );
    }
}

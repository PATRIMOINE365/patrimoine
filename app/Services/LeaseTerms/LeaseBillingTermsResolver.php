<?php

namespace App\Services\LeaseTerms;

use App\Models\Lease;
use App\Models\LeaseTermVersion;
use Carbon\CarbonInterface;

/**
 * Resolve the contractual Lease terms that applied on a billing date.
 *
 * Current operational terms continue to live on Lease, while immutable
 * LeaseTermVersion rows preserve the contractual state that applied
 * historically.
 *
 * Invoice generation must use the historical version applicable to the
 * billing period rather than blindly using the newest Lease terms.
 */
class LeaseBillingTermsResolver
{
    /**
     * Resolve a Lease-shaped billing context for the supplied date.
     *
     * A cloned Lease is returned so callers can reuse the established
     * InvoiceGenerationService calculation helpers without mutating the
     * persisted Lease.
     *
     * Leases created directly by older tests/internal code may not have a
     * term version. In that compatibility case, current Lease terms remain
     * authoritative.
     */
    public function resolve(
        Lease $lease,
        CarbonInterface $asOf
    ): Lease {
        $version =
            LeaseTermVersion::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->whereDate(
                    'effective_from',
                    '<=',
                    $asOf->toDateString()
                )
                ->where(
                    function ($query) use ($asOf): void {
                        $query
                            ->whereNull(
                                'effective_to'
                            )
                            ->orWhereDate(
                                'effective_to',
                                '>=',
                                $asOf->toDateString()
                            );
                    }
                )
                ->orderByDesc(
                    'version_number'
                )
                ->first();

        if ($version === null) {
            return clone $lease;
        }

        $resolved =
            clone $lease;

        foreach (
            LeaseTermVersionService::TERM_FIELDS as $field
        ) {
            if (
                array_key_exists(
                    $field,
                    $version->terms
                )
            ) {
                $resolved->setAttribute(
                    $field,
                    $version->terms[$field]
                );
            }
        }

        return $resolved;
    }
}

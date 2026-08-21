<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use Carbon\CarbonImmutable;

/**
 * Read-only tenant Arrears Aging report.
 *
 * Every issued or partially settled rent Invoice whose due date has
 * arrived contributes its ledger-derived outstanding amount to an aging
 * bucket based on how many days overdue it is on the as-of date:
 *
 * - current  : 0-30 days
 * - 31-60    : 31-60 days
 * - 61-90    : 61-90 days
 * - over 90  : more than 90 days
 *
 * Outstanding amounts are always derived from settlement records
 * (payment allocations, fund consumption and deposit applications),
 * never from a stored balance.
 */
class ArrearsReportService
{
    /**
     * @var list<string>
     */
    private const BUCKETS = [
        'current',
        'days_31_60',
        'days_61_90',
        'over_90',
    ];

    /**
     * Generate the Arrears Aging snapshot.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters = []): array
    {
        $asOf = $this->resolveAsOfDate($filters);

        $invoices = Invoice::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
            ])
            ->where('type', 'rent')
            ->whereIn('status', ['issued', 'partial'])
            ->whereDate(
                'due_date',
                '<=',
                $asOf->toDateString()
            )
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $totals = $this->emptyBucketRow();

        $totals['invoice_count'] = 0;

        /** @var array<int, array<string, mixed>> $tenants */
        $tenants = [];

        foreach ($invoices as $invoice) {
            $outstanding = $invoice->outstandingAmount();

            if ($outstanding <= 0) {
                continue;
            }

            $bucket = $this->bucketFor(
                $invoice,
                $asOf
            );

            $totals[$bucket] += $outstanding;
            $totals['total'] += $outstanding;
            $totals['invoice_count']++;

            $leaseId = (int) $invoice->lease_id;

            if (! isset($tenants[$leaseId])) {
                $tenants[$leaseId] = $this->newTenantRow(
                    $invoice
                );
            }

            $tenants[$leaseId][$bucket] += $outstanding;
            $tenants[$leaseId]['total'] += $outstanding;
            $tenants[$leaseId]['invoice_count']++;
        }

        return [
            'filters' => $filters,

            'as_of' => $asOf->toDateString(),

            'totals' => $totals,

            'tenants' => collect($tenants)
                ->sortByDesc('total')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveAsOfDate(
        array $filters
    ): CarbonImmutable {
        if (! empty($filters['as_of'])) {
            return CarbonImmutable::createFromFormat(
                '!Y-m-d',
                (string) $filters['as_of'],
                config('app.timezone')
            );
        }

        return CarbonImmutable::today(
            config('app.timezone')
        );
    }

    /**
     * @return array<string, int>
     */
    private function emptyBucketRow(): array
    {
        $row = [];

        foreach (self::BUCKETS as $bucket) {
            $row[$bucket] = 0;
        }

        $row['total'] = 0;

        return $row;
    }

    private function bucketFor(
        Invoice $invoice,
        CarbonImmutable $asOf
    ): string {
        $daysOverdue =
            (int) $invoice->due_date
                ->toImmutable()
                ->startOfDay()
                ->diffInDays(
                    $asOf,
                    true
                );

        return match (true) {
            $daysOverdue <= 30 => 'current',

            $daysOverdue <= 60 => 'days_31_60',

            $daysOverdue <= 90 => 'days_61_90',

            default => 'over_90',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function newTenantRow(
        Invoice $invoice
    ): array {
        $lease = $invoice->lease;
        $tenant = $lease?->tenant;
        $unit = $lease?->unit;
        $building = $unit?->building;

        return [
            'tenant' => [
                'id' => $tenant?->id,

                'name' => $tenant?->name
                    ?? $tenant?->legal_name,
            ],

            'lease' => [
                'id' => $lease?->id,
            ],

            'building' => [
                'id' => $building?->id,
                'name' => $building?->name,
            ],

            'unit' => [
                'id' => $unit?->id,
                'name' => $unit?->name,
            ],

            'invoice_count' => 0,

            ...$this->emptyBucketRow(),
        ];
    }
}

<?php

namespace App\Services\Reports;

use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only portfolio Occupancy report.
 *
 * The report is an as-of snapshot: a Unit is occupied when an active or
 * notice-stage Lease covers the requested date. Occupancy is always
 * derived from Lease records and never stored on the Unit, mirroring the
 * dashboard's occupancy derivation.
 */
class OccupancyReportService
{
    /**
     * Generate the Occupancy snapshot.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters = []): array
    {
        $asOf = $this->resolveAsOfDate($filters);

        $units = Unit::query()
            ->with('building')
            ->orderBy('building_id')
            ->orderBy('name')
            ->get();

        $occupiedUnitIds = Unit::query()
            ->whereHas(
                'leases',
                fn (Builder $leaseQuery) => $this->applyOccupancyWindow(
                    $leaseQuery,
                    $asOf
                )
            )
            ->pluck('id')
            ->all();

        $isOccupied =
            fn (Unit $unit): bool => in_array(
                $unit->id,
                $occupiedUnitIds,
                true
            );

        $totalUnits = $units->count();

        $occupiedUnits =
            $units
                ->filter($isOccupied)
                ->count();

        $commercialUnits =
            $units->filter(
                fn (Unit $unit): bool => (bool) $unit->is_commercial
            );

        $residentialUnits =
            $units->filter(
                fn (Unit $unit): bool => ! $unit->is_commercial
            );

        return [
            'filters' => $filters,

            'as_of' => $asOf->toDateString(),

            'totals' => [
                'units' => $totalUnits,

                'occupied' => $occupiedUnits,

                'vacant' => $totalUnits - $occupiedUnits,

                'occupancy_rate' => $this->rate(
                    $occupiedUnits,
                    $totalUnits
                ),
            ],

            'classification' => [
                'commercial' => $this->classificationSection(
                    $commercialUnits->count(),
                    $commercialUnits->filter($isOccupied)->count()
                ),

                'residential' => $this->classificationSection(
                    $residentialUnits->count(),
                    $residentialUnits->filter($isOccupied)->count()
                ),
            ],

            'buildings' => $units
                ->groupBy('building_id')
                ->map(
                    fn ($buildingUnits): array => $this->serializeBuilding(
                        $buildingUnits->all(),
                        $isOccupied
                    )
                )
                ->sortBy(
                    'name',
                    SORT_NATURAL | SORT_FLAG_CASE
                )
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
     * Restrict a Lease query to leases creating occupancy on the as-of
     * date. Draft and terminated Leases never create occupancy.
     *
     * @param  Builder<\App\Models\Lease>  $query
     * @return Builder<\App\Models\Lease>
     */
    private function applyOccupancyWindow(
        Builder $query,
        CarbonImmutable $asOf
    ): Builder {
        return $query
            ->whereIn('status', ['active', 'notice'])
            ->whereDate(
                'start_date',
                '<=',
                $asOf->toDateString()
            )
            ->where(
                function (Builder $endQuery) use ($asOf): void {
                    $endQuery
                        ->whereNull('end_date')
                        ->orWhereDate(
                            'end_date',
                            '>=',
                            $asOf->toDateString()
                        );
                }
            );
    }

    /**
     * @return array<string, int>
     */
    private function classificationSection(
        int $total,
        int $occupied
    ): array {
        return [
            'units' => $total,

            'occupied' => $occupied,

            'vacant' => $total - $occupied,

            'occupancy_rate' => $this->rate(
                $occupied,
                $total
            ),
        ];
    }

    /**
     * @param  list<Unit>  $buildingUnits
     * @param  callable(Unit): bool  $isOccupied
     * @return array<string, mixed>
     */
    private function serializeBuilding(
        array $buildingUnits,
        callable $isOccupied
    ): array {
        $building = $buildingUnits[0]->building;

        $total = count($buildingUnits);

        $occupied =
            count(
                array_filter(
                    $buildingUnits,
                    $isOccupied
                )
            );

        $commercial =
            count(
                array_filter(
                    $buildingUnits,
                    fn (Unit $unit): bool => (bool) $unit->is_commercial
                )
            );

        return [
            'id' => $building?->id,

            'name' => $building?->name,

            'units' => $total,

            'occupied' => $occupied,

            'vacant' => $total - $occupied,

            'occupancy_rate' => $this->rate(
                $occupied,
                $total
            ),

            'commercial_units' => $commercial,
        ];
    }

    /**
     * Occupancy rate in whole percent (0 when no units exist).
     */
    private function rate(
        int $occupied,
        int $total
    ): int {
        if ($total === 0) {
            return 0;
        }

        return (int) round(
            $occupied * 100 / $total
        );
    }
}

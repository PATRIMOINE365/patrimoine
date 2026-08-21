<?php

namespace App\Services\Reports;

use App\Models\OwnerAccount;
use App\Models\TenantFundAccount;
use Carbon\CarbonImmutable;

/**
 * Read-only Funds Held report.
 *
 * A snapshot, always as of today, of the money Patrimoine currently
 * holds on behalf of others:
 *
 * - TENANT funds: Rent Reserve, Consumable Advance and Security Deposit
 *   balances derived from the tenant-fund ledger (credits - debits) on
 *   active accounts;
 * - OWNER funds: positive owner-account balances derived from the owner
 *   ledger (credits - debits).
 *
 * Negative balances represent obligations owed to Patrimoine and never
 * reduce funds genuinely held for others, mirroring the dashboard's
 * funds-held metrics.
 */
class FundsReportService
{
    /**
     * @var list<string>
     */
    private const TENANT_FUND_TYPES = [
        'rent_reserve',
        'consumable_advance',
        'security_deposit',
    ];

    /**
     * Generate the Funds Held snapshot.
     *
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        return [
            'as_of' => CarbonImmutable::today(
                config('app.timezone')
            )->toDateString(),

            'tenant_funds' => $this->tenantFunds(),

            'owner_funds' => $this->ownerFunds(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantFunds(): array
    {
        $accounts = TenantFundAccount::query()
            ->where('status', 'active')
            ->with([
                'transactions',
                'lease.tenant',
                'lease.unit.building',
            ])
            ->get();

        $summary = [];

        $totalHeld = 0;

        foreach (self::TENANT_FUND_TYPES as $type) {
            $positive =
                $accounts
                    ->where('type', $type)
                    ->map(
                        fn (TenantFundAccount $account): int => $account->balance()
                    )
                    ->filter(
                        fn (int $balance): bool => $balance > 0
                    );

            $held = (int) $positive->sum();

            $summary[$type] = [
                'account_count' => $positive->count(),

                'total_held' => $held,
            ];

            $totalHeld += $held;
        }

        $summary['total_held'] = $totalHeld;

        return [
            'summary' => $summary,

            'tenants' => $accounts
                ->groupBy('lease_id')
                ->map(
                    fn ($leaseAccounts): array => $this->serializeTenantRow(
                        $leaseAccounts->all()
                    )
                )
                ->filter(
                    fn (array $row): bool => $row['total'] > 0
                )
                ->sortByDesc('total')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<TenantFundAccount>  $leaseAccounts
     * @return array<string, mixed>
     */
    private function serializeTenantRow(
        array $leaseAccounts
    ): array {
        $lease = $leaseAccounts[0]->lease;
        $tenant = $lease?->tenant;
        $unit = $lease?->unit;
        $building = $unit?->building;

        $row = [
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
        ];

        $total = 0;

        foreach (self::TENANT_FUND_TYPES as $type) {
            $held = 0;

            foreach ($leaseAccounts as $account) {
                if ($account->type !== $type) {
                    continue;
                }

                $held += max(
                    0,
                    $account->balance()
                );
            }

            $row[$type] = $held;

            $total += $held;
        }

        $row['total'] = $total;

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function ownerFunds(): array
    {
        $owners = OwnerAccount::query()
            ->with('party')
            ->get()
            ->map(
                fn (OwnerAccount $account): array => [
                    'owner' => [
                        'id' => $account->party?->id,

                        'name' => $account->party?->name
                            ?? $account->party?->legal_name,
                    ],

                    'balance' => $account->balance(),
                ]
            )
            ->filter(
                fn (array $row): bool => $row['balance'] > 0
            )
            ->sortByDesc('balance')
            ->values();

        return [
            'summary' => [
                'account_count' => $owners->count(),

                'total_held' => (int) $owners->sum('balance'),
            ],

            'owners' => $owners->all(),
        ];
    }
}

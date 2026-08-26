<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Organisation;
use App\Services\LicensingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Platform overview for the administration console.
 *
 * Reads across every customer organisation; the internal platform
 * organisation is excluded from every figure.
 */
class AdminDashboardController extends Controller
{
    /**
     * The platform-wide picture.
     */
    public function __invoke(
        LicensingService $licensing
    ): JsonResponse {
        $customers = Organisation::query()
            ->customers()
            ->with('licenses')
            ->get();

        $plans = [
            'free' => 0,
            'standard' => 0,
            'professional' => 0,
        ];

        $onTrial = 0;
        $licensed = 0;

        foreach ($customers as $organisation) {
            $plan = $licensing->planFor($organisation);

            $plans[$plan] = ($plans[$plan] ?? 0) + 1;

            if ($licensing->onTrialFor($organisation)) {
                $onTrial++;
            } elseif ($plan !== 'free') {
                $licensed++;
            }
        }

        /*
         * Trials and licences ending within the next 14 days.
         */
        $horizon = now()->addDays(14)->toDateString();

        $expiringTrials = $customers
            ->filter(
                fn (Organisation $organisation): bool =>
                    $licensing->onTrialFor($organisation)
                    && $organisation->trial_ends_on !== null
                    && $organisation->trial_ends_on->toDateString() <= $horizon
            )
            ->map(
                fn (Organisation $organisation): array => [
                    'organisation_id' => $organisation->id,
                    'organisation' => $organisation->name,
                    'kind' => 'trial',
                    'plan' => (string) config('licensing.trial_plan'),
                    'ends_on' => $organisation->trial_ends_on->toDateString(),
                ]
            )
            ->values();

        $expiringLicenses = License::query()
            ->whereNull('revoked_at')
            ->whereNotNull('expires_on')
            ->whereBetween('expires_on', [
                now()->toDateString(),
                $horizon,
            ])
            ->whereIn(
                'organisation_id',
                $customers->pluck('id')
            )
            ->with('organisation')
            ->orderBy('expires_on')
            ->get()
            ->map(
                fn (License $license): array => [
                    'organisation_id' => $license->organisation_id,
                    'organisation' => (string) $license->organisation?->name,
                    'kind' => 'license',
                    'plan' => $license->plan,
                    'ends_on' => $license->expires_on->toDateString(),
                ]
            )
            ->values();

        /*
         * Heaviest email users this month.
         */
        $topEmailUsage = DB::table('organisation_email_counters')
            ->where('period', now()->format('Y-m'))
            ->whereIn('organisation_id', $customers->pluck('id'))
            ->orderByRaw('(automated_sent + transactional_sent) desc')
            ->limit(5)
            ->get()
            ->map(
                function ($row) use ($customers): array {
                    $organisation = $customers->firstWhere('id', $row->organisation_id);

                    return [
                        'organisation_id' => (int) $row->organisation_id,
                        'organisation' => (string) ($organisation?->name ?? '—'),
                        'sent' => (int) $row->automated_sent
                            + (int) $row->transactional_sent,
                    ];
                }
            );

        return response()->json([
            'totals' => [
                'organisations' => $customers->count(),
                'active' => $customers->where('status', 'active')->count(),
                'suspended' => $customers->where('status', 'suspended')->count(),
                'on_trial' => $onTrial,
                'licensed' => $licensed,
                'plans' => $plans,
            ],
            'signups_this_month' => $customers
                ->filter(
                    fn (Organisation $organisation): bool =>
                        $organisation->created_at !== null
                        && $organisation->created_at->isSameMonth(now())
                )
                ->count(),
            'expiring_soon' => $expiringTrials
                ->concat($expiringLicenses)
                ->sortBy('ends_on')
                ->values(),
            'top_email_usage' => $topEmailUsage,
        ]);
    }
}

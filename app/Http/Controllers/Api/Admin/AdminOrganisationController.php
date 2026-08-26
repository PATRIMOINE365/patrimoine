<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Organisation;
use App\Models\User;
use App\Services\LicensingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer organisations: list and detail for the platform console.
 */
class AdminOrganisationController extends Controller
{
    /**
     * Searchable customer list.
     */
    public function index(
        Request $request,
        LicensingService $licensing
    ): JsonResponse {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,suspended'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Organisation::query()
            ->customers()
            ->with('licenses')
            ->orderByDesc('id');

        if (filled($validated['search'] ?? null)) {
            $query->where(
                'name',
                'like',
                '%'.$validated['search'].'%'
            );
        }

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        $page = $query->paginate(25);

        return response()->json([
            'data' => collect($page->items())->map(
                function (Organisation $organisation) use ($licensing): array {
                    $plan = $licensing->planFor($organisation);

                    $covering = $organisation->licenses->first(
                        fn (License $license): bool => $license->coversToday()
                    );

                    return [
                        'id' => $organisation->id,
                        'name' => $organisation->name,
                        'status' => $organisation->status,
                        'plan' => $plan,
                        'on_trial' => $licensing->onTrialFor($organisation),
                        'trial_ends_on' => $organisation->trial_ends_on?->toDateString(),
                        'created_at' => $organisation->created_at?->toDateString(),
                        'usage' => $licensing->usageFor($organisation),
                        'limits' => config('licensing.plans.'.$plan.'.limits', []),
                        'current_license' => $covering === null
                            ? null
                            : [
                                'id' => $covering->id,
                                'plan' => $covering->plan,
                                'starts_on' => $covering->starts_on?->toDateString(),
                                'expires_on' => $covering->expires_on?->toDateString(),
                            ],
                    ];
                }
            ),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * One organisation in full: plan, usage, licences and users.
     */
    public function show(
        int $organisationId,
        LicensingService $licensing
    ): JsonResponse {
        $organisation = Organisation::query()
            ->customers()
            ->with('licenses')
            ->findOrFail($organisationId);

        $plan = $licensing->planFor($organisation);

        return response()->json([
            'organisation' => [
                'id' => $organisation->id,
                'name' => $organisation->name,
                'status' => $organisation->status,
                'plan' => $plan,
                'on_trial' => $licensing->onTrialFor($organisation),
                'trial_ends_on' => $organisation->trial_ends_on?->toDateString(),
                'created_at' => $organisation->created_at?->toDateTimeString(),
            ],
            'limits' => config('licensing.plans.'.$plan.'.limits', []),
            'usage' => $licensing->usageFor($organisation),
            'licenses' => $organisation->licenses
                ->map(
                    fn (License $license): array => [
                        'id' => $license->id,
                        'plan' => $license->plan,
                        'starts_on' => $license->starts_on?->toDateString(),
                        'expires_on' => $license->expires_on?->toDateString(),
                        'amount' => $license->amount,
                        'currency' => $license->currency,
                        'payment_method' => $license->payment_method,
                        'payment_reference' => $license->payment_reference,
                        'notes' => $license->notes,
                        'revoked_at' => $license->revoked_at?->toDateTimeString(),
                        'covers_today' => $license->coversToday(),
                        'created_at' => $license->created_at?->toDateString(),
                    ]
                )
                ->values(),
            'users' => User::withoutGlobalScopes()
                ->where('organisation_id', $organisation->id)
                ->orderBy('id')
                ->get()
                ->map(
                    fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => (bool) $user->is_active,
                        'email_verified' => $user->email_verified_at !== null,
                        'created_at' => $user->created_at?->toDateString(),
                    ]
                )
                ->values(),
        ]);
    }
}

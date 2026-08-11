<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\ApplicationSetting;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * REST API controller for Patrimoine Parties.
 *
 * Controllers intentionally contain only HTTP/application orchestration.
 * Domain and financial business rules remain outside controllers.
 */
class PartyController extends Controller
{
    /**
     * Return a paginated list of Parties.
     *
     * Supported filters:
     * - type;
     * - role;
     * - free-text search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Party::query()
            ->with('roles');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('role')) {
            $role = $request->string('role')->toString();

            $query->whereHas(
                'roles',
                fn ($query) =>
                    $query->where('role', $role)
            );
        }

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere(
                        'registration_number',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $parties = $query
            ->orderBy('id', 'desc')
            ->paginate(
                perPage: min(
                    max((int) $request->input('per_page', 25), 1),
                    100
                )
            );

        return response()->json($parties);
    }

    /**
     * Create a new Party and any supplied Party roles.
     */
    public function store(
        StorePartyRequest $request
    ): JsonResponse {
        $party = DB::transaction(function () use ($request): Party {
            $validated = $request->validated();

            $roles = $validated['roles'] ?? [];

            /*
             * Roles live in their own table and must not be passed into
             * Party::create().
             */
            unset($validated['roles']);

            $party = Party::create($validated);

            foreach ($roles as $role) {
                $party->roles()->create([
                    'role' => $role,
                ]);
            }

            return $party->load('roles');
        });

        return response()->json(
            data: $party,
            status: 201
        );
    }

    /**
     * Return one Party with its functional roles.
     */
    public function show(Party $party): JsonResponse
    {
        return response()->json(
            $party->load('roles')
        );
    }

    /**
     * Update an existing Party.
     *
     * When roles are supplied they replace the current role set.
     * When roles are omitted, the current role assignments are preserved.
     *
     * The configured managing organisation is protected from losing its
     * managing_organisation role through this generic Party endpoint.
     */
    public function update(
        UpdatePartyRequest $request,
        Party $party
    ): JsonResponse {
        $party = DB::transaction(
            function () use ($request, $party): Party {
                $validated = $request->validated();

                $rolesSupplied = array_key_exists(
                    'roles',
                    $validated
                );

                $roles = $validated['roles'] ?? [];

                /*
                 * The Party designated as Patrimoine's application-wide
                 * managing organisation must retain that role.
                 */
                if (
                    $rolesSupplied
                    && $this->isConfiguredManagingOrganisation($party)
                    && ! in_array(
                        'managing_organisation',
                        $roles,
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'roles' => [
                            'The configured managing organisation cannot lose the managing_organisation role.',
                        ],
                    ]);
                }

                unset($validated['roles']);

                $party->update($validated);

                if ($rolesSupplied) {
                    $party->roles()->delete();

                    foreach ($roles as $role) {
                        $party->roles()->create([
                            'role' => $role,
                        ]);
                    }
                }

                return $party
                    ->refresh()
                    ->load('roles');
            }
        );

        return response()->json($party);
    }

    /**
     * Delete a Party when database relationships allow it.
     *
     * The configured managing organisation is application identity and may
     * not be removed through the generic Party API.
     *
     * Foreign-key restrictions continue to protect other contractual and
     * financial Party relationships from accidental deletion.
     */
    public function destroy(Party $party): JsonResponse
    {
        if ($this->isConfiguredManagingOrganisation($party)) {
            return response()->json(
                [
                    'message' =>
                        'The configured managing organisation cannot be deleted.',
                ],
                409
            );
        }

        $party->delete();

        return response()->json(
            data: null,
            status: 204
        );
    }

    /**
     * Determine whether a Party is the application-wide managing organisation.
     */
    private function isConfiguredManagingOrganisation(
        Party $party
    ): bool {
        return ApplicationSetting::query()
            ->where(
                'managing_organisation_party_id',
                $party->id
            )
            ->exists();
    }
}

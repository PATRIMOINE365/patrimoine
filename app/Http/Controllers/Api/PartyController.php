<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

                return $party->refresh()->load('roles');
            }
        );

        return response()->json($party);
    }

    /**
     * Delete a Party when database relationships allow it.
     *
     * Foreign-key restrictions protect financial and contractual history
     * from being silently removed.
     */
    public function destroy(Party $party): JsonResponse
    {
        $party->delete();

        return response()->json(
            data: null,
            status: 204
        );
    }
}

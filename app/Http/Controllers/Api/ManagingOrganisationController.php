<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateManagingOrganisationRequest;
use App\Models\ApplicationSetting;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Manage the single Patrimoine managing organisation.
 *
 * Patrimoine 1.0 is not multi-tenant. Exactly one Party is designated as
 * the application-wide managing organisation.
 */
class ManagingOrganisationController extends Controller
{
    /**
     * Return the currently configured managing organisation.
     */
    public function show(): JsonResponse
    {
        $settings = ApplicationSetting::query()
            ->with('managingOrganisation.roles')
            ->first();

        if (
            $settings === null
            || $settings->managingOrganisation === null
        ) {
            return response()->json(
                [
                    'message' =>
                        'Managing organisation has not been configured.',
                ],
                404
            );
        }

        return response()->json(
            $settings->managingOrganisation
        );
    }

    /**
     * Create or update the singleton managing organisation.
     */
    public function update(
        UpdateManagingOrganisationRequest $request
    ): JsonResponse {
        $party = DB::transaction(
            function () use ($request): Party {
                $settings = ApplicationSetting::query()
                    ->lockForUpdate()
                    ->first();

                /*
                 * The configuration row is created lazily so a fresh install
                 * does not require seed data before the first configuration.
                 */
                if ($settings === null) {
                    $settings = ApplicationSetting::create();
                }

                $validated = $request->validated();

                /*
                 * Managing organisations are always Organisation Parties.
                 * The API therefore does not expose Party type as user input.
                 */
                $partyAttributes = array_merge(
                    $validated,
                    [
                        'type' => 'organisation',
                    ]
                );

                if ($settings->managing_organisation_party_id === null) {
                    $party = Party::create($partyAttributes);

                    $settings->update([
                        'managing_organisation_party_id' =>
                            $party->id,
                    ]);
                } else {
                    $party = Party::query()
                        ->findOrFail(
                            $settings->managing_organisation_party_id
                        );

                    $party->update($partyAttributes);
                }

                /*
                 * Ensure the application identity always carries the
                 * managing_organisation functional role.
                 */
                $party->roles()->firstOrCreate([
                    'role' => 'managing_organisation',
                ]);

                return $party
                    ->refresh()
                    ->load('roles');
            }
        );

        return response()->json($party);
    }
}

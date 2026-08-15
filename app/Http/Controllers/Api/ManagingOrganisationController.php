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
 *
 * Application-wide financial defaults remain on ApplicationSetting rather
 * than on the Party because they configure Patrimoine behaviour rather than
 * describe the legal organisation itself.
 */
class ManagingOrganisationController extends Controller
{
    /**
     * Return the currently configured managing organisation together with
     * application-wide financial defaults.
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
            $this->responseData(
                $settings
            )
        );
    }

    /**
     * Create or update the singleton managing organisation and its
     * application-wide defaults.
     */
    public function update(
        UpdateManagingOrganisationRequest $request
    ): JsonResponse {
        $settings = DB::transaction(
            function () use ($request): ApplicationSetting {
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

                $validated =
                    $request->validated();

                /*
                 * Financial defaults configure the application rather than
                 * describe the Managing Organisation Party.
                 *
                 * Extract them before passing organisation attributes into
                 * Party::create() or Party::update().
                 */
                $defaultVatRate =
                    $validated['default_vat_rate'];

                $language =
                    $validated['language'];

                $currency =
                    $validated['currency'];

                unset(
                    $validated[
                        'default_vat_rate'
                    ],
                    $validated[
                        'language'
                    ],
                    $validated[
                        'currency'
                    ]
                );

                /*
                 * Managing organisations are always Organisation Parties.
                 * The API therefore does not expose Party type as user input.
                 */
                $partyAttributes =
                    array_merge(
                        $validated,
                        [
                            'type' =>
                                'organisation',
                        ]
                    );

                if (
                    $settings
                        ->managing_organisation_party_id
                    === null
                ) {
                    $party =
                        Party::create(
                            $partyAttributes
                        );

                    $settings->update([
                        'managing_organisation_party_id' =>
                            $party->id,
                    ]);
                } else {
                    $party =
                        Party::query()
                            ->findOrFail(
                                $settings
                                    ->managing_organisation_party_id
                            );

                    $party->update(
                        $partyAttributes
                    );
                }

                /*
                 * Store financial defaults independently from Party identity.
                 *
                 * Existing Leases and Invoices retain their own VAT snapshots;
                 * this value applies only when creating future records.
                 */
                $settings->update([
                    'default_vat_rate' =>
                        $defaultVatRate,

                    'language' =>
                        $language,

                    'currency' =>
                        $currency,
                ]);

                /*
                 * Ensure the application identity always carries the
                 * managing_organisation functional role.
                 */
                $party
                    ->roles()
                    ->firstOrCreate([
                        'role' =>
                            'managing_organisation',
                    ]);

                return $settings
                    ->refresh()
                    ->load(
                        'managingOrganisation.roles'
                    );
            }
        );

        return response()->json(
            $this->responseData(
                $settings
            )
        );
    }

    /**
     * Build the API representation of Managing Organisation configuration.
     *
     * Organisation identity remains represented by Party while financial
     * defaults are merged into the response for the Settings workspace.
     *
     * @return array<string, mixed>
     */
    private function responseData(
        ApplicationSetting $settings
    ): array {
        $party =
            $settings
                ->managingOrganisation;

        return array_merge(
            $party->toArray(),
            [
                'default_vat_rate' =>
                    $settings
                        ->default_vat_rate,

                'language' =>
                    $settings->language
                    ?? config(
                        'patrimoine.defaults.language',
                        'en'
                    ),

                'currency' =>
                    $settings->currency
                    ?? config(
                        'patrimoine.defaults.currency',
                        'GHS'
                    ),
            ]
        );
    }
}

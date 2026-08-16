<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitialSetupRequest;
use App\Models\ApplicationSetting;
use App\Models\Party;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Perform Patrimoine's one-time initial installation.
 *
 * This controller is intentionally narrow. It exists only to bootstrap a
 * fresh installation with:
 *
 * - the first Administrator;
 * - the Managing Organisation;
 * - organisation-wide language/currency and VAT defaults.
 *
 * It must never become a general account-registration endpoint.
 */
class InitialSetupController extends Controller
{
    /**
     * Return whether the one-time setup workflow is still available.
     *
     * No sensitive information is exposed. Browser clients need only know
     * whether setup must be completed before normal login can be used.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'setup_required' =>
                $this->setupRequired(),
        ]);
    }

    /**
     * Complete the one-time initial installation.
     *
     * The whole bootstrap is transactional so Patrimoine cannot be left with
     * a user but no Managing Organisation, or vice versa, if creation fails.
     */
    public function store(
        InitialSetupRequest $request
    ): JsonResponse {
        /*
         * Reject setup immediately if this installation has ever been
         * bootstrapped already.
         *
         * A user OR a configured Managing Organisation is sufficient to
         * disable this public endpoint.
         */
        if (! $this->setupRequired()) {
            return response()->json(
                [
                    'message' =>
                        'Initial setup has already been completed.',
                ],
                409
            );
        }

        $validated =
            $request->validated();

        $result = DB::transaction(
            function () use ($validated): array {
                /*
                 * Re-check inside the transaction.
                 *
                 * This protects against two setup submissions arriving close
                 * together during installation.
                 */
                if (! $this->setupRequired()) {
                    return [
                        'already_configured' =>
                            true,
                    ];
                }

                /*
                 * The User model hashes passwords through its existing
                 * password cast.
                 */
                $user =
                    User::create([
                        'name' =>
                            trim(
                                $validated[
                                    'administrator_name'
                                ]
                            ),

                        'email' =>
                            mb_strtolower(
                                trim(
                                    $validated[
                                        'administrator_email'
                                    ]
                                )
                            ),

                        'password' =>
                            $validated[
                                'administrator_password'
                            ],

                        /*
                         * A fresh V1.0.3 installation always creates the
                         * first application User as Administrator.
                         */
                        'role' =>
                            UserRole::Administrator,
                    ]);

                /*
                 * The first Administrator chooses their own password during
                 * trusted Initial Setup and therefore does not use the later
                 * invitation workflow.
                 *
                 * Verification is set explicitly rather than making
                 * email_verified_at generally mass assignable on User.
                 */
                $user->forceFill([
                    'email_verified_at' =>
                        now(),
                ])->save();

                /*
                 * Managing Organisation remains a normal Organisation Party.
                 */
                $organisation =
                    Party::create([
                        'type' =>
                            'organisation',

                        'legal_name' =>
                            $validated[
                                'legal_name'
                            ],

                        'address' =>
                            $validated[
                                'address'
                            ],

                        'phone' =>
                            $validated[
                                'phone'
                            ]
                            ?? null,

                        'email' =>
                            $validated[
                                'email'
                            ]
                            ?? null,

                        'contact_person_name' =>
                            $validated[
                                'contact_person_name'
                            ],

                        'contact_person_phone' =>
                            $validated[
                                'contact_person_phone'
                            ],

                        'contact_person_email' =>
                            $validated[
                                'contact_person_email'
                            ],
                    ]);

                $organisation
                    ->roles()
                    ->create([
                        'role' =>
                            'managing_organisation',
                    ]);

                /*
                 * Language and currency belong to the installation through
                 * ApplicationSetting, not to the administrator user.
                 */
                $settings =
                    ApplicationSetting::create([
                        'managing_organisation_party_id' =>
                            $organisation->id,

                        'default_vat_rate' =>
                            $validated[
                                'default_vat_rate'
                            ],

                        'language' =>
                            $validated[
                                'language'
                            ],

                        'currency' =>
                            $validated[
                                'currency'
                            ],
                    ]);

                return [
                    'already_configured' =>
                        false,

                    'user' =>
                        $user,

                    'organisation' =>
                        $organisation,

                    'settings' =>
                        $settings,
                ];
            }
        );

        if (
            $result[
                'already_configured'
            ]
        ) {
            return response()->json(
                [
                    'message' =>
                        'Initial setup has already been completed.',
                ],
                409
            );
        }

        return response()->json(
            [
                'message' =>
                    'Initial setup completed successfully.',

                'language' =>
                    $result[
                        'settings'
                    ]->language,

                'currency' =>
                    $result[
                        'settings'
                    ]->currency,
            ],
            201
        );
    }

    /**
     * Setup is available only on a genuinely unconfigured installation.
     */
    private function setupRequired(): bool
    {
        if (
            User::query()
                ->exists()
        ) {
            return false;
        }

        $settings =
            ApplicationSetting::query()
                ->first();

        return $settings === null
            || $settings
                ->managing_organisation_party_id
                === null;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Support\PhoneField;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Public self-service signup for new Patrimoine 365 organisations.
 *
 * Registration is the multi-tenant successor of the retired one-time
 * setup wizard: each signup provisions a complete, isolated
 * organisation with its first Administrator, who must verify their
 * email address before the account can sign in.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registration
    ) {
    }

    /**
     * Create a new organisation and its first administrator.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organisation_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'given_names' => [
                'required',
                'string',
                'max:255',
            ],

            'surname' => [
                'required',
                'string',
                'max:255',
            ],

            /*
             * Emails are platform-wide identities: one address, one
             * account, exactly one organisation.
             */
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',

                /*
                 * V1.0.11: the platform's own domain can never own a
                 * customer organisation — staff accounts exist only
                 * through platform invitations.
                 */
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (
                        str_ends_with(
                            mb_strtolower(trim((string) $value)),
                            '@'.\App\Models\User::PLATFORM_EMAIL_DOMAIN
                        )
                    ) {
                        $fail(__('api.registration.platform_domain_blocked'));
                    }
                },
            ],

            'phone' => PhoneField::number('phone'),

            'phone_country' => PhoneField::country('phone'),

            'password' => [
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],

            'language' => [
                'required',
                'in:en,fr',
            ],

            /*
             * Signup is impossible without accepting the Terms of
             * Service and Privacy Policy.
             */
            'accept_legal' => [
                'required',
                'accepted',
            ],
        ]);

        $user = $this->registration->register(
            $validated,
            $request
        );

        return response()->json(
            [
                'message' => __('api.registration.created'),
                'email' => $user->email,
            ],
            201
        );
    }

    /**
     * Consume an email-verification token.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'size:64',
            ],
        ]);

        $user = $this->registration->verifyEmail(
            $validated['token'],
            $request
        );

        if ($user === null) {
            return response()->json(
                [
                    'message' => __('api.registration.verification_invalid'),
                ],
                422
            );
        }

        return response()->json([
            'message' => __('api.registration.verified'),
            'email' => $user->email,
        ]);
    }

    /**
     * Issue a fresh verification link.
     *
     * Always answers 200: the endpoint never discloses whether an
     * address exists or is already verified.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $this->registration->resendVerification(
            $validated['email']
        );

        return response()->json([
            'message' => __('api.registration.verification_sent'),
        ]);
    }
}

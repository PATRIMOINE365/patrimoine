<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication endpoints for Patrimoine application users.
 *
 * Patrimoine users are administrative accounts and remain separate from
 * domain Parties such as owners, tenants and agents.
 *
 * Sanctum personal access tokens are supported for API and future mobile
 * clients. The first-party web application may later use Sanctum's
 * stateful session authentication instead.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user and issue a Sanctum personal access token.
     *
     * The plain-text token is returned only at creation time. Sanctum
     * stores only the hashed representation in the database.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        /*
         * Look up the account independently from the password so the same
         * generic validation response can be returned for either an unknown
         * email address or an incorrect password.
         */
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (
            $user === null
            || ! Hash::check(
                $credentials['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        /*
         * A descriptive device name makes individual tokens identifiable
         * should Patrimoine later expose active-session management.
         */
        $tokenName = $credentials['device_name']
            ?? 'patrimoine-api';

        $token = $user->createToken($tokenName);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'user' => $this->serializeUser($user),
        ]);
    }

    /**
     * Return the currently authenticated Patrimoine user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(
            $this->serializeUser($user)
        );
    }

    /**
     * Revoke the Sanctum token used for the current API request.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /*
         * API-token authentication provides a current access token.
         * The null-safe operator also keeps this endpoint compatible with
         * future stateful Sanctum authentication.
         */
        $user->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Produce the public API representation of an application user.
     *
     * Authentication responses deliberately expose only the fields needed
     * by clients and never return password or remember-token information.
     *
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}

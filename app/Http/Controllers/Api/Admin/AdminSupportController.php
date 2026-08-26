<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use App\Services\PlatformAuditService;
use App\Services\RegistrationService;
use App\Services\UserPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The support tools customers actually write in about: verification
 * emails, locked accounts and forgotten passwords.
 *
 * Deliberately operates only on CUSTOMER organisations — platform
 * staff manage themselves through their own Users page.
 */
class AdminSupportController extends Controller
{
    /**
     * Re-send a customer user's verification email.
     */
    public function resendVerification(
        int $userId,
        Request $request,
        RegistrationService $registration,
        PlatformAuditService $audit
    ): JsonResponse {
        [$user, $organisation] = $this->customerUser($userId);

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'user' => ['This account is already verified.'],
            ]);
        }

        $registration->resendVerification($user->email);

        $audit->record(
            action: 'platform.verification_resent',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    /**
     * Activate or deactivate a customer user.
     */
    public function setActive(
        int $userId,
        Request $request,
        PlatformAuditService $audit
    ): JsonResponse {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        [$user, $organisation] = $this->customerUser($userId);

        $activating = (bool) $validated['is_active'];

        if (! $activating) {
            /*
             * Never strand a customer: their last active administrator
             * cannot be deactivated from the console.
             */
            $otherActiveAdministrators = User::withoutGlobalScopes()
                ->where('organisation_id', $organisation->id)
                ->where('role', 'administrator')
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->exists();

            if (
                $user->role->value === 'administrator'
                && ! $otherActiveAdministrators
            ) {
                throw ValidationException::withMessages([
                    'user' => [
                        'This is the organisation\'s last active administrator.',
                    ],
                ]);
            }
        }

        $user->forceFill([
            'is_active' => $activating,
        ])->save();

        if (! $activating) {
            /*
             * A deactivated account loses its sessions immediately.
             */
            $user->tokens()->delete();
        }

        $audit->record(
            action: $activating
                ? 'platform.user_reactivated'
                : 'platform.user_deactivated',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => $activating
                ? 'User reactivated.'
                : 'User deactivated.',
        ]);
    }

    /**
     * Send a customer user a password-reset link.
     */
    public function sendPasswordReset(
        int $userId,
        Request $request,
        UserPasswordService $passwords,
        PlatformAuditService $audit
    ): JsonResponse {
        [$user, $organisation] = $this->customerUser($userId);

        $passwords->sendResetLink($user->email);

        $audit->record(
            action: 'platform.password_reset_sent',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => 'Password reset link sent.',
        ]);
    }

    /**
     * Resolve a user belonging to a CUSTOMER organisation.
     *
     * @return array{0: User, 1: Organisation}
     */
    private function customerUser(int $userId): array
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);

        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($user->organisation_id);

        return [$user, $organisation];
    }
}

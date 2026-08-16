<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptUserInvitationRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserInvitationService;
use App\Services\UserPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public and authenticated password ownership workflows.
 */
class PasswordController extends Controller
{
    /**
     * Complete first-time account setup from a valid invitation.
     */
    public function acceptInvitation(
        AcceptUserInvitationRequest $request,
        UserInvitationService $invitations,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated = $request->validated();

        $user = $invitations->accept(
            email: $validated['email'],
            plainToken: $validated['token'],
            password: $validated['password']
        );

        /*
         * Invitation acceptance is a successful account-ownership action.
         * No invitation token or password is retained in Activity Log.
         */
        $activityLog->record(
            action: 'auth.invitation_accepted',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => __(
                'api.user_invitation.accepted'
            ),
        ]);
    }

    /**
     * Request a password-reset email without exposing account existence.
     */
    public function forgot(
        ForgotPasswordRequest $request,
        UserPasswordService $passwords
    ): JsonResponse {
        $passwords->sendResetLink(
            $request->validated('email')
        );

        return response()->json([
            'message' => __(
                'api.password.reset_requested'
            ),
        ]);
    }

    /**
     * Consume a valid password-reset token.
     */
    public function reset(
        ResetPasswordRequest $request,
        UserPasswordService $passwords,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated = $request->validated();

        $passwords->reset(
            email: $validated['email'],
            token: $validated['token'],
            password: $validated['password']
        );

        /*
         * Resolve the successfully reset account only after the reset has
         * completed. The password and reset token are never logged.
         */
        $user = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        $activityLog->record(
            action: 'auth.password_reset',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => __(
                'api.password.reset_complete'
            ),
        ]);
    }

    /**
     * Change the currently authenticated User's own password.
     */
    public function change(
        ChangePasswordRequest $request,
        UserPasswordService $passwords,
        ActivityLogService $activityLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $passwords->changeOwnPassword(
            user: $user,
            currentPassword:
                $validated['current_password'],
            newPassword:
                $validated['password']
        );

        $activityLog->record(
            action: 'auth.password_changed',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => __(
                'api.password.changed'
            ),
        ]);
    }
}

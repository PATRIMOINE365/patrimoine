<?php

namespace App\Http\Controllers\Api\Admin\Concerns;

use App\Models\Organisation;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * V1.0.51: the console asks for the administrator's own password again
 * before anything that locks a customer out or moves their account.
 *
 * A bearer token is a credential that can be copied; the password is
 * the thing that stays in the person's head. Until now only deletion
 * asked for it, and nothing counted the failures — so a token in the
 * wrong hands could guess the password at leisure. Every failure is
 * now written to the platform's own trail, and the routes that use
 * this are throttled in their own buckets.
 */
trait ReentersPassword
{
    /**
     * Refuse unless the request carries the signed-in administrator's
     * current password.
     *
     * @param  string  $action  what the re-entry guards, for the log
     * @param  string  $field  the request field carrying the password
     */
    protected function requirePasswordReentry(
        Request $request,
        string $action,
        ?Organisation $customer = null,
        string $field = 'current_password'
    ): void {
        $validated = $request->validate([
            $field => ['required', 'string'],
        ]);

        $admin = $request->user();

        if (Hash::check($validated[$field], $admin->password)) {
            return;
        }

        app(ActivityLogService::class)->record(
            action: 'platform.password_reentry_failed',
            actor: $admin,
            request: $request,
            entityType: 'user',
            entityId: $admin->id,
            entityLabel: $admin->name,
            metadata: [
                'for' => $action,
                'customer_organisation_id' => $customer?->id,
                'customer_organisation' => $customer?->name,
            ],
            organisationId: (int) $admin->organisation_id,
        );

        throw ValidationException::withMessages([
            $field => [
                __('api.auth.password_confirmation_failed'),
            ],
        ]);
    }
}

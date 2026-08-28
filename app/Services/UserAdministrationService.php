<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Perform security-sensitive User administration mutations.
 *
 * This service owns the V1.0.3 Administrator lockout safeguards.
 *
 * User Management controllers must call these methods rather than checking
 * the rules separately and then modifying the User outside the transaction.
 * Keeping the check and mutation together prevents the application from
 * accidentally leaving Patrimoine without an active Administrator.
 */
class UserAdministrationService
{
    /**
     * Change a User's application role.
     *
     * Administrators may not change their own role. When another
     * Administrator is being demoted, at least one other active
     * Administrator must remain.
     */
    public function changeRole(
        User $actor,
        User $target,
        UserRole $newRole
    ): User {
        return DB::transaction(
            function () use (
                $actor,
                $target,
                $newRole
            ): User {
                $lockedTarget = $this->lockUser($target);

                /*
                 * A no-op does not actually change the role and therefore
                 * cannot reduce Administrator coverage.
                 */
                if ($lockedTarget->role === $newRole) {
                    return $lockedTarget;
                }

                if ($actor->is($lockedTarget)) {
                    throw ValidationException::withMessages([
                        'role' => __(
                            'api.user_management.cannot_change_own_role'
                        ),
                    ]);
                }

                if (
                    $lockedTarget->role === UserRole::Administrator
                    && $newRole !== UserRole::Administrator
                    && $lockedTarget->isActive()
                ) {
                    $this->assertAnotherActiveAdministratorExists(
                        $lockedTarget
                    );
                }

                $lockedTarget->role = $newRole;
                $lockedTarget->save();

                return $lockedTarget->refresh();
            }
        );
    }

    /**
     * Enable or disable a User account.
     *
     * An Administrator cannot disable their own account. Disabling another
     * active Administrator is permitted only when another active
     * Administrator remains.
     */
    public function changeActiveStatus(
        User $actor,
        User $target,
        bool $isActive
    ): User {
        return DB::transaction(
            function () use (
                $actor,
                $target,
                $isActive
            ): User {
                $lockedTarget = $this->lockUser($target);

                /*
                 * Preserve idempotent status updates.
                 */
                if ($lockedTarget->isActive() === $isActive) {
                    return $lockedTarget;
                }

                if (
                    ! $isActive
                    && $actor->is($lockedTarget)
                ) {
                    throw ValidationException::withMessages([
                        'is_active' => __(
                            'api.user_management.cannot_disable_self'
                        ),
                    ]);
                }

                if (
                    ! $isActive
                    && $lockedTarget->role === UserRole::Administrator
                    && $lockedTarget->isActive()
                ) {
                    $this->assertAnotherActiveAdministratorExists(
                        $lockedTarget
                    );
                }

                $lockedTarget->is_active = $isActive;
                $lockedTarget->save();

                /*
                 * An account created dormant has never been invited.
                 * Activation is the moment it becomes usable, so that is
                 * when the invitation goes out -- and only for someone
                 * who has never taken ownership of the account.
                 */
                if ($isActive) {
                    app(UserInvitationService::class)
                        ->sendIfNeverAccepted(
                            $lockedTarget->refresh()
                        );
                }

                /*
                 * Disabling an account takes effect immediately for bearer
                 * authentication by revoking every outstanding Sanctum token.
                 */
                if (! $isActive) {
                    $lockedTarget->tokens()->delete();
                }

                return $lockedTarget->refresh();
            }
        );
    }

    /**
     * Delete a User account.
     *
     * Historical identity preservation will be connected by the later
     * Activity Log/User Management work. This method owns only the
     * Administrator safety invariant and the atomic deletion operation.
     */
    public function delete(
        User $actor,
        User $target
    ): void {
        DB::transaction(
            function () use (
                $actor,
                $target
            ): void {
                $lockedTarget = $this->lockUser($target);

                if ($actor->is($lockedTarget)) {
                    throw ValidationException::withMessages([
                        'user' => __(
                            'api.user_management.cannot_delete_self'
                        ),
                    ]);
                }

                if (
                    $lockedTarget->role === UserRole::Administrator
                    && $lockedTarget->isActive()
                ) {
                    $this->assertAnotherActiveAdministratorExists(
                        $lockedTarget
                    );
                }

                $lockedTarget->delete();
            }
        );
    }

    /**
     * Lock and reload the target User inside the current transaction.
     *
     * Never trust a potentially stale model supplied by the controller for
     * a security-sensitive role/status/delete decision.
     */
    private function lockUser(User $user): User
    {
        return User::query()
            ->whereKey($user->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Ensure at least one other active Administrator exists.
     *
     * The matching Administrator rows are locked before counting them so
     * concurrent administrative changes serialize on databases that
     * support row-level SELECT ... FOR UPDATE locking.
     */
    private function assertAnotherActiveAdministratorExists(
        User $excludedUser
    ): void {
        $activeAdministrators = User::query()
            ->where(
                'role',
                UserRole::Administrator->value
            )
            ->where('is_active', true)
            ->whereKeyNot($excludedUser->getKey())
            ->lockForUpdate()
            ->get();

        if ($activeAdministrators->isEmpty()) {
            throw ValidationException::withMessages([
                'administrator' => __(
                    'api.user_management.last_active_administrator'
                ),
            ]);
        }
    }
}

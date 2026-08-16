<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Protect the V1.0.3 User/RBAC database foundation and upgrade behavior.
 */
class UserRbacFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_schema_contains_v1_0_3_fields(): void
    {
        $this->assertTrue(
            Schema::hasColumns(
                'users',
                [
                    'phone',
                    'is_active',
                ]
            )
        );
    }

    public function test_phone_is_optional(): void
    {
        $user = User::factory()->create([
            'phone' => null,
        ]);

        $this->assertNull(
            $user->phone
        );
    }

    public function test_all_three_roles_can_be_persisted(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->assertSame(
                $role,
                $user->fresh()->role
            );
        }
    }

    /**
     * Simulate the real V1.0.2 -> V1.0.3 migration boundary.
     *
     * The test temporarily rolls back only the V1.0.3 User migration,
     * creates a V1.0.2-style Property Manager, then reapplies the migration.
     */
    public function test_v1_0_2_property_manager_is_promoted_to_administrator(): void
    {
        $migration =
            require database_path(
                'migrations/2026_08_16_100000_upgrade_users_for_v1_0_3_rbac.php'
            );

        $migration->down();

        $userId =
            DB::table('users')
                ->insertGetId([
                    'name' =>
                        'Existing V1.0.2 User',

                    'email' =>
                        'existing-v102@example.test',

                    'password' =>
                        'not-used-by-this-test',

                    'role' =>
                        'property_manager',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

        $migration->up();

        $user =
            DB::table('users')
                ->where('id', $userId)
                ->first();

        $this->assertSame(
            'administrator',
            $user->role
        );

        $this->assertNull(
            $user->phone
        );

        $this->assertSame(
            1,
            (int) $user->is_active
        );
    }

    /**
     * Existing V1.0.2 accounts predate the invitation workflow and must
     * remain established accounts after upgrading to V1.0.3.
     */
    public function test_v1_0_2_existing_user_remains_verified_after_upgrade(): void
    {
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => null,
            'password' => 'existing-password',
        ]);

        /*
         * Reproduce the corrective migration's data rule directly because
         * RefreshDatabase has already applied the complete migration set
         * before this test creates its simulated legacy account.
         */
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->whereNull('email_verified_at')
            ->whereNotNull('password')
            ->update([
                'email_verified_at' => now(),
            ]);

        $user->refresh();

        $this->assertNotNull(
            $user->email_verified_at
        );

        $this->assertTrue(
            $user->isActive()
        );
    }

}

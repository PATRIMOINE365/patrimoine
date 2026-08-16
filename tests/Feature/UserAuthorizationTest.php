<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verify the V1.0.3 User/RBAC foundation.
 *
 * Full capability authorization is introduced in Activity C.
 */
class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_manager_can_access_legacy_business_api(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::PropertyManager,
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/dashboard')
            ->assertOk();
    }

    public function test_administrator_can_access_legacy_business_api(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Administrator,
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/dashboard')
            ->assertOk();
    }

    public function test_viewer_is_forbidden_from_legacy_business_api(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/dashboard')
            ->assertForbidden();
    }

    public function test_viewer_can_access_own_authentication_identity(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(
                'role',
                UserRole::Viewer->value
            );
    }

    public function test_exactly_three_application_roles_exist(): void
    {
        $this->assertSame(
            [
                'administrator',
                'property_manager',
                'viewer',
            ],
            array_map(
                static fn (UserRole $role): string =>
                    $role->value,
                UserRole::cases()
            )
        );
    }

    public function test_generic_user_factory_remains_property_manager(): void
    {
        $user = User::factory()->create();

        $this->assertSame(
            UserRole::PropertyManager,
            $user->role
        );

        $this->assertTrue(
            $user->isActive()
        );
    }
}

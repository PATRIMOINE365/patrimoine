<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verify role-based authorization for Patrimoine application users.
 */
class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A Property Manager can access Patrimoine business endpoints.
     */
    public function test_property_manager_can_access_business_api(): void
    {
        $user = User::factory()->create([
            'role' => 'property_manager',
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/dashboard')
            ->assertOk();
    }

    /**
     * An authenticated user without the required application role is denied.
     */
    public function test_user_without_property_manager_role_is_forbidden(): void
    {
        $user = User::factory()->create([
            'role' => 'viewer',
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/dashboard')
            ->assertForbidden();
    }

    /**
     * An unauthorized user may still retrieve their own authentication
     * identity so clients can explain why application access was denied.
     */
    public function test_non_manager_can_still_access_me(): void
    {
        $user = User::factory()->create([
            'name' => 'Restricted User',
            'role' => 'viewer',
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(
                'name',
                'Restricted User'
            )
            ->assertJsonPath(
                'role',
                'viewer'
            );
    }

    /**
     * The Property Manager role is returned through authentication APIs.
     */
    public function test_me_returns_property_manager_role(): void
    {
        $user = User::factory()->create([
            'role' => 'property_manager',
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(
                'role',
                'property_manager'
            );
    }

    /**
     * Newly created Patrimoine users default to the Property Manager role.
     */
    public function test_user_factory_defaults_to_property_manager(): void
    {
        $user = User::factory()->create();

        $this->assertSame(
            'property_manager',
            $user->role
        );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
                'role' => 'property_manager',
            ]
        );
    }
}

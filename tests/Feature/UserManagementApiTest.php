<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verify the Administrator-only V1.0.3 User Management API.
 */
class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_requires_authentication(): void
    {
        $this
            ->getJson('/api/users')
            ->assertUnauthorized();
    }

    public function test_only_administrator_can_access_user_management(): void
    {
        foreach (
            [
                UserRole::PropertyManager,
                UserRole::Viewer,
            ] as $role
        ) {
            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,
                ])
            );

            $this
                ->getJson('/api/users')
                ->assertForbidden();

            $this
                ->postJson('/api/users', [])
                ->assertForbidden();
        }
    }

    public function test_administrator_can_list_users(): void
    {
        $administrator = $this->administrator([
            'name' => 'Primary Administrator',
        ]);

        User::factory()->create([
            'name' => 'Property Manager One',
            'role' => UserRole::PropertyManager,
        ]);

        User::factory()->create([
            'name' => 'Viewer One',
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath(
                'total',
                3
            )
            ->assertJsonCount(
                3,
                'data'
            );
    }

    public function test_user_directory_supports_search_role_and_status_filters(): void
    {
        $administrator = $this->administrator();

        User::factory()->create([
            'name' => 'Needle Viewer',
            'email' => 'needle@example.test',
            'phone' => '0244000001',
            'role' => UserRole::Viewer,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Needle Manager',
            'email' => 'manager@example.test',
            'role' => UserRole::PropertyManager,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Inactive Needle Viewer',
            'email' => 'inactive@example.test',
            'role' => UserRole::Viewer,
            'is_active' => false,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->getJson(
                '/api/users?search=Needle&role=viewer&is_active=1'
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.email',
                'needle@example.test'
            );
    }

    public function test_administrator_can_create_user_without_default_password(): void
    {
        $administrator = $this->administrator();

        Sanctum::actingAs($administrator);

        $response =
            $this->postJson(
                '/api/users',
                [
                    'name' => 'New Manager',
                    'email' => 'NEW.MANAGER@EXAMPLE.TEST',
                    'phone' => '0244000002',
                    'role' =>
                        UserRole::PropertyManager->value,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'New Manager'
            )
            ->assertJsonPath(
                'email',
                'new.manager@example.test'
            )
            ->assertJsonPath(
                'role',
                UserRole::PropertyManager->value
            )
            ->assertJsonPath(
                'is_active',
                true
            )
            ->assertJsonMissingPath(
                'password'
            );

        $user = User::query()->findOrFail(
            $response->json('id')
        );

        $this->assertNull(
            $user->email_verified_at
        );

        /*
         * The backend must not silently establish a predictable default
         * password before Activity F's invitation/password-setup flow.
         */
        $this->assertFalse(
            Hash::check(
                'password',
                $user->password
            )
        );
    }

    public function test_create_user_validates_required_fields_and_role(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->postJson(
                '/api/users',
                [
                    'role' => 'not-a-role',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'role',
            ]);
    }

    public function test_user_email_must_be_unique(): void
    {
        $administrator = $this->administrator();

        User::factory()->create([
            'email' => 'existing@example.test',
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->postJson(
                '/api/users',
                [
                    'name' => 'Duplicate Email',
                    'email' => 'EXISTING@EXAMPLE.TEST',
                    'role' =>
                        UserRole::Viewer->value,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_administrator_can_show_and_update_user(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'name' => 'Before Name',
            'email' => 'before@example.test',
            'phone' => null,
            'role' => UserRole::PropertyManager,
            'is_active' => true,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->getJson(
                "/api/users/{$target->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'email',
                'before@example.test'
            );

        $this
            ->patchJson(
                "/api/users/{$target->id}",
                [
                    'name' => 'After Name',
                    'email' => 'AFTER@EXAMPLE.TEST',
                    'phone' => '0244000003',
                    'role' =>
                        UserRole::Viewer->value,
                    'is_active' => false,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'name',
                'After Name'
            )
            ->assertJsonPath(
                'email',
                'after@example.test'
            )
            ->assertJsonPath(
                'phone',
                '0244000003'
            )
            ->assertJsonPath(
                'role',
                UserRole::Viewer->value
            )
            ->assertJsonPath(
                'is_active',
                false
            );
    }

    public function test_update_can_keep_same_email(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'email' => 'same@example.test',
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$target->id}",
                [
                    'email' => 'SAME@EXAMPLE.TEST',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'email',
                'same@example.test'
            );
    }

    public function test_http_update_uses_own_role_safeguard(): void
    {
        $administrator = $this->administrator();

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$administrator->id}",
                [
                    'role' =>
                        UserRole::Viewer->value,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.role.0',
                'You cannot change your own Administrator role.'
            );
    }

    public function test_http_update_uses_self_disable_safeguard(): void
    {
        $administrator = $this->administrator();

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$administrator->id}",
                [
                    'is_active' => false,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.is_active.0',
                'You cannot disable your own account.'
            );
    }

    public function test_http_delete_uses_self_delete_safeguard(): void
    {
        $administrator = $this->administrator();

        Sanctum::actingAs($administrator);

        $this
            ->deleteJson(
                "/api/users/{$administrator->id}"
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.user.0',
                'You cannot delete your own account.'
            );
    }

    public function test_last_active_administrator_is_protected_through_api(): void
    {
        $actor = $this->administrator([
            'is_active' => false,
        ]);

        $target = $this->administrator();

        Sanctum::actingAs($actor);

        $this
            ->patchJson(
                "/api/users/{$target->id}",
                [
                    'role' =>
                        UserRole::PropertyManager->value,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.administrator.0',
                'This action cannot be completed because Patrimoine must retain at least one active Administrator.'
            );

        $this->assertSame(
            UserRole::Administrator,
            $target->fresh()->role
        );
    }

    public function test_administrator_can_delete_another_user(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->deleteJson(
                "/api/users/{$target->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'users',
            [
                'id' => $target->id,
            ]
        );
    }

    public function test_deleted_user_email_can_be_reused(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'email' => 'reusable@example.test',
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->deleteJson(
                "/api/users/{$target->id}"
            )
            ->assertNoContent();

        $this
            ->postJson(
                '/api/users',
                [
                    'name' => 'Replacement User',
                    'email' => 'reusable@example.test',
                    'role' =>
                        UserRole::Viewer->value,
                ]
            )
            ->assertCreated();
    }

    public function test_safeguard_message_renders_in_french_through_api(): void
    {
        /*
         * HTTP requests use Patrimoine's organisation-wide language setting.
         * Configure the application itself rather than setting Laravel's
         * locale directly, because ApplyApplicationLocale runs on every
         * request and is the production source of truth.
         */
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $administrator = $this->administrator();

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$administrator->id}",
                [
                    'is_active' => false,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.is_active.0',
                'Vous ne pouvez pas désactiver votre propre compte.'
            );
    }

    /**
     * Create an active Administrator for API tests.
     *
     * @param array<string, mixed> $attributes
     */
    private function administrator(
        array $attributes = []
    ): User {
        return User::factory()->create(
            array_merge(
                [
                    'role' => UserRole::Administrator,
                    'is_active' => true,
                ],
                $attributes
            )
        );
    }
}

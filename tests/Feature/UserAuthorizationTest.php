<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verify V1.0.3 central server-side RBAC.
 *
 * These tests deliberately call API endpoints directly so authorization
 * cannot be bypassed merely by hiding browser controls.
 */
class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_capability_matrix_is_frozen(): void
    {
        foreach (UserCapability::cases() as $capability) {
            $this->assertTrue(
                UserRole::Administrator->allows($capability)
            );
        }

        $this->assertTrue(
            UserRole::PropertyManager->allows(
                UserCapability::ViewOperations
            )
        );
        $this->assertTrue(
            UserRole::PropertyManager->allows(
                UserCapability::ManageOperations
            )
        );
        $this->assertTrue(
            UserRole::PropertyManager->allows(
                UserCapability::ManageFinance
            )
        );
        $this->assertTrue(
            UserRole::PropertyManager->allows(
                UserCapability::ExportReports
            )
        );
        /*
         * V1.0.7: the Manager mirrors the Administrator outside the
         * Manage group, so record deletion is a Manager capability.
         */
        $this->assertTrue(
            UserRole::PropertyManager->allows(
                UserCapability::DeleteRecords
            )
        );
        $this->assertFalse(
            UserRole::PropertyManager->allows(
                UserCapability::ManageSettings
            )
        );
        $this->assertFalse(
            UserRole::PropertyManager->allows(
                UserCapability::ManageUsers
            )
        );
        $this->assertFalse(
            UserRole::PropertyManager->allows(
                UserCapability::ViewActivityLog
            )
        );
        $this->assertFalse(
            UserRole::PropertyManager->allows(
                UserCapability::ViewFinancialJournal
            )
        );

        $this->assertTrue(
            UserRole::Viewer->allows(
                UserCapability::ViewOperations
            )
        );
        $this->assertTrue(
            UserRole::Viewer->allows(
                UserCapability::ExportReports
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::ManageOperations
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::ManageFinance
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::DeleteRecords
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::ManageSettings
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::ManageUsers
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::ViewActivityLog
            )
        );
        $this->assertFalse(
            UserRole::Viewer->allows(
                UserCapability::ViewFinancialJournal
            )
        );
    }

    public function test_all_three_roles_can_read_operational_api(): void
    {
        foreach (UserRole::cases() as $role) {
            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,
                ])
            );

            $this
                ->getJson('/api/dashboard')
                ->assertOk();

            $this
                ->getJson('/api/parties')
                ->assertOk();
        }
    }

    public function test_viewer_cannot_create_operational_record(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Viewer,
            ])
        );

        $this
            ->postJson(
                '/api/parties',
                []
            )
            ->assertForbidden();
    }

    public function test_property_manager_reaches_operational_write_validation(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::PropertyManager,
            ])
        );

        /*
         * 422 proves authorization allowed the request through to validation.
         */
        $this
            ->postJson(
                '/api/parties',
                []
            )
            ->assertUnprocessable();
    }

    public function test_administrator_reaches_operational_write_validation(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Administrator,
            ])
        );

        $this
            ->postJson(
                '/api/parties',
                []
            )
            ->assertUnprocessable();
    }

    public function test_only_administrator_can_access_settings(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Administrator,
            ])
        );

        /*
         * The endpoint may return 404 when no Managing Organisation exists,
         * but must not be blocked by authorization.
         */
        $this->assertNotSame(
            403,
            $this
                ->getJson('/api/managing-organisation')
                ->getStatusCode()
        );

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
                ->getJson('/api/managing-organisation')
                ->assertForbidden();

            $this
                ->putJson(
                    '/api/managing-organisation',
                    []
                )
                ->assertForbidden();
        }
    }

    /*
     * V1.0.7: record deletion belongs to the Manager as well — only the
     * Viewer remains blocked. Manager deletion itself is covered by
     * test_manager_can_delete_supported_unreferenced_record below.
     */
    public function test_viewer_cannot_delete(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Delete Protection Test',
            'phone' => '0200000000',
            'email' => 'viewer@delete-test.example',
        ]);

        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Viewer,
            ])
        );

        $this
            ->deleteJson("/api/parties/{$party->id}")
            ->assertForbidden();

        $this->assertDatabaseHas(
            'parties',
            [
                'id' => $party->id,
            ]
        );
    }

    public function test_manager_can_delete_supported_unreferenced_record(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Manager Delete Test',
            'phone' => '0200000002',
            'email' => 'manager-delete-test@example.test',
        ]);

        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::PropertyManager,
            ])
        );

        $this
            ->deleteJson("/api/parties/{$party->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'parties',
            [
                'id' => $party->id,
            ]
        );
    }

    public function test_administrator_can_delete_supported_unreferenced_record(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Administrator Delete Test',
            'phone' => '0200000001',
            'email' => 'administrator-delete-test@example.test',
        ]);

        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Administrator,
            ])
        );

        $this
            ->deleteJson("/api/parties/{$party->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'parties',
            [
                'id' => $party->id,
            ]
        );
    }

    public function test_viewer_cannot_perform_financial_write(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Viewer,
            ])
        );

        $this
            ->postJson(
                '/api/payments',
                []
            )
            ->assertForbidden();
    }

    public function test_property_manager_reaches_financial_write_validation(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::PropertyManager,
            ])
        );

        $this
            ->postJson(
                '/api/payments',
                []
            )
            ->assertUnprocessable();
    }

    public function test_viewer_can_access_applicable_report_api(): void
    {
        /*
         * Building also has no factory. For an authorization test we only
         * need a persisted Building that route-model binding can resolve.
         */
        $building = Building::create([
            'name' => 'Viewer Report Test Building',
        ]);

        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Viewer,
            ])
        );

        $response =
            $this->getJson(
                "/api/reports/buildings/{$building->id}"
            );

        /*
         * This assertion deliberately checks authorization only. Any later
         * report-domain failure is separate from the Viewer RBAC rule.
         */
        $this->assertNotSame(
            403,
            $response->getStatusCode()
        );
    }

    public function test_invalid_capability_fails_closed(): void
    {
        Sanctum::actingAs(
            User::factory()->create([
                'role' => UserRole::Administrator,
            ])
        );

        $this->app['router']
            ->get(
                '/_test/invalid-capability',
                fn () => response()->json(['ok' => true])
            )
            ->middleware([
                'auth:sanctum',
                'capability:not_a_real_capability',
            ]);

        $this
            ->getJson('/_test/invalid-capability')
            ->assertForbidden();
    }

    public function test_authenticated_user_can_still_access_own_identity(): void
    {
        $user = User::factory()->create([
            'name' => 'Viewer User',
            'role' => UserRole::Viewer,
        ]);

        Sanctum::actingAs($user);

        $this
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(
                'name',
                'Viewer User'
            )
            ->assertJsonPath(
                'role',
                UserRole::Viewer->value
            );
    }

    public function test_all_three_roles_can_read_lease_register(): void
    {
        foreach (UserRole::cases() as $role) {
            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,
                ])
            );

            $this
                ->getJson('/api/leases')
                ->assertOk();
        }
    }
}

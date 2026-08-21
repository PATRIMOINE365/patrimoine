<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use Tests\TestCase;

/**
 * Freeze the V1.0.3 role-aware browser presentation rules.
 *
 * API authorization has separate feature tests and remains authoritative.
 */
class RoleAwareApplicationUiTest extends TestCase
{
    public function test_server_role_matrix_matches_frozen_h_behavior(): void
    {
        $this->assertTrue(
            UserRole::Administrator->allows(
                UserCapability::ManageOperations
            )
        );

        $this->assertTrue(
            UserRole::Administrator->allows(
                UserCapability::ManageFinance
            )
        );

        $this->assertTrue(
            UserRole::Administrator->allows(
                UserCapability::DeleteRecords
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

        /*
         * V1.0.7: the Manager mirrors the Administrator outside the
         * Manage group, so record deletion is allowed…
         */
        $this->assertTrue(
            UserRole::PropertyManager->allows(
                UserCapability::DeleteRecords
            )
        );

        // …while the Manage group itself stays Administrator-only.
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
    }

    public function test_browser_has_central_capability_layer(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/permissions.js'
                )
            );

        foreach (
            [
                'administrator',
                'property_manager',
                'viewer',
                'manage_operations',
                'manage_finance',
                'delete_records',
                'export_reports',
                'manage_settings',
                'manage_users',
            ] as $value
        ) {
            $this->assertStringContainsString(
                $value,
                $javascript
            );
        }

        $this->assertStringContainsString(
            'MutationObserver',
            $javascript
        );
    }

    public function test_common_write_controls_are_classified(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/permissions.js'
                )
            );

        foreach (
            [
                '#add-property-button',
                '[data-edit-property]',
                '[data-edit-unit]',
                '#add-party-button',
                '[data-edit-party]',
                '#add-lease-button',
                '[data-extend-lease]',
                '#record-payment-button',
                '#owner-record-deposit-button',
                '#owner-record-expense-button',
                '#owner-record-payout-button',
                '#owner-record-adjustment-button',

                /*
                 * V1.0.7: the lease-page tenant-funds and security-deposit
                 * drawers were removed (their work lives in the Tenants
                 * workspace), so their selectors left the classification.
                 * The new finance controls are classified instead.
                 */
                '#owner-expense-bill-submit',
                '#tenant-transfer-form',
                '[data-transfer-source]',
                '#tenant-deposit-form',
                '#tenant-withdrawal-form',
                '#tenant-adjustment-form',
                '[data-delete-party]',
                '[data-delete-lease]',
            ] as $selector
        ) {
            $this->assertStringContainsString(
                $selector,
                $javascript
            );
        }
    }

    public function test_viewer_exports_are_not_hidden_as_writes(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/permissions.js'
                )
            );

        /*
         * Document and report downloads remain available through the
         * export_reports capability for Viewer.
         */
        $this->assertStringNotContainsString(
            '[data-receipt-endpoint]',
            $javascript
        );

        $this->assertStringNotContainsString(
            'reports.pdf',
            $javascript
        );

        $this->assertStringNotContainsString(
            'reports.csv',
            $javascript
        );
    }

    public function test_settings_and_users_navigation_are_administrator_only(): void
    {
        $layout =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        $this->assertStringContainsString(
            'data-requires-capability="manage_users"',
            $layout
        );

        $this->assertStringContainsString(
            'data-requires-capability="manage_settings"',
            $layout
        );

        $this->assertStringContainsString(
            'data-requires-capability="view_activity_log"',
            $layout
        );
    }

    public function test_rbac_visibility_cannot_be_overridden_by_normal_hidden_state(): void
    {
        $css =
            file_get_contents(
                resource_path(
                    'css/app.css'
                )
            );

        $this->assertStringContainsString(
            '.rbac-hidden',
            $css
        );

        $this->assertStringContainsString(
            'display: none !important',
            $css
        );
    }

    public function test_presentation_configuration_exposes_only_display_identity(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/Api/ApplicationPresentationController.php'
                )
            );

        $this->assertStringContainsString(
            "'organisation_name'",
            $controller
        );

        $this->assertStringContainsString(
            'managingOrganisation()',
            $controller
        );
    }

    public function test_administrator_users_workspace_is_not_permanently_hidden(): void
    {
        $view = file_get_contents(
            resource_path('views/app/users.blade.php')
        );

        $this->assertIsString($view);

        $this->assertStringContainsString(
            'id="users-workspace"',
            $view
        );

        /*
         * Access to /users is controlled by Activity H's centralized
         * Administrator capability authorization. The workspace itself
         * must therefore not carry an unconditional Tailwind hidden class
         * that would leave an authorized Administrator with a blank page.
         */
        $this->assertDoesNotMatchRegularExpression(
            '/id="users-workspace"[\s\S]{0,150}class="hidden"/',
            $view
        );
    }

    public function test_lease_defaults_do_not_require_administrator_settings_access(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/leases.js'
                )
            );

        $this->assertIsString(
            $javascript
        );

        $this->assertStringContainsString(
            "'/api/presentation-config'",
            $javascript
        );

        $this->assertStringNotContainsString(
            "'/api/managing-organisation'",
            $javascript
        );
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeaseTerminationUiTest extends TestCase
{
    public function test_lease_ui_exposes_controlled_termination_lifecycle(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/leases.js'
                )
            );

        $blade =
            file_get_contents(
                resource_path(
                    'views/app/leases.blade.php'
                )
            );

        $permissions =
            file_get_contents(
                resource_path(
                    'js/permissions.js'
                )
            );

        $this->assertStringContainsString(
            '[data-terminate-lease]',
            $javascript
        );

        $this->assertStringContainsString(
            'openLeaseTerminationModal',
            $javascript
        );

        $this->assertStringContainsString(
            'submitLeaseTerminationForm',
            $javascript
        );

        $this->assertStringContainsString(
            '/termination',
            $javascript
        );

        $this->assertStringContainsString(
            'termination-notice/pdf',
            $javascript
        );

        $this->assertStringContainsString(
            'id="lease-termination-modal"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-termination-notice-date"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-termination-date"',
            $blade
        );

        foreach (
            [
                'prorate',
                'full',
                'none',
            ] as $mode
        ) {
            $this->assertStringContainsString(
                'value="'.$mode.'"',
                $blade
            );
        }

        $this->assertStringContainsString(
            '[data-terminate-lease]',
            $permissions
        );
    }

    public function test_termination_translations_exist_in_both_languages(): void
    {
        $translations =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        foreach (
            [
                'leases.terminate',
                'leases.terminate_lease',
                'leases.termination_date',
                'leases.final_rent_prorate',
                'leases.final_rent_full',
                'leases.final_rent_none',
                'leases.initiate_termination',
                'leases.open_termination_notice',
            ] as $key
        ) {
            $this->assertSame(
                2,
                substr_count(
                    $translations,
                    "'{$key}':"
                ),
                "Expected English and French translations for {$key}."
            );
        }
    }


    public function test_termination_in_progress_ui_exposes_settlement_workflow(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/leases.js'
                )
            );

        $blade =
            file_get_contents(
                resource_path(
                    'views/app/leases.blade.php'
                )
            );

        $this->assertStringContainsString(
            'data-termination-settlement',
            $javascript
        );

        $this->assertStringContainsString(
            '/termination-settlement',
            $javascript
        );

        $this->assertStringContainsString(
            '/termination/complete',
            $javascript
        );

        $this->assertStringContainsString(
            '/termination/cancel',
            $javascript
        );

        $this->assertStringContainsString(
            'id="termination-settlement-modal"',
            $blade
        );

        $this->assertStringContainsString(
            'id="termination-settlement-blockers"',
            $blade
        );

        $this->assertStringContainsString(
            'id="termination-settlement-complete"',
            $blade
        );

        $this->assertStringContainsString(
            'id="termination-settlement-cancel"',
            $blade
        );
    }

    public function test_termination_settlement_hands_financial_resolution_to_tenant_workspace(): void
    {
        $leaseJavascript =
            file_get_contents(
                resource_path(
                    'js/leases.js'
                )
            );

        $tenantJavascript =
            file_get_contents(
                resource_path(
                    'js/tenants.js'
                )
            );

        $this->assertStringContainsString(
            '/tenants?tenant_id=',
            $leaseJavascript
        );

        $this->assertStringContainsString(
            "'tenant_id'",
            $tenantJavascript
        );

        $this->assertStringContainsString(
            'requestedTenantId',
            $tenantJavascript
        );
    }

    public function test_security_deposit_deductions_are_available_during_notice_but_final_settlement_remains_terminated_only(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/leases.js'
                )
            );

        $this->assertStringContainsString(
            'const terminationInProgress',
            $javascript
        );

        $this->assertStringContainsString(
            'const deductionsAllowed',
            $javascript
        );

        $this->assertStringContainsString(
            'deductionForm?.classList.remove',
            $javascript
        );

        $this->assertStringContainsString(
            'if (terminated)',
            $javascript
        );

        $this->assertStringContainsString(
            'settlementForm?.classList.remove',
            $javascript
        );
    }

    public function test_settlement_mutations_are_classified_for_browser_rbac(): void
    {
        $permissions =
            file_get_contents(
                resource_path(
                    'js/permissions.js'
                )
            );

        $this->assertStringContainsString(
            '#termination-settlement-complete',
            $permissions
        );

        $this->assertStringContainsString(
            '#termination-settlement-cancel',
            $permissions
        );

        /*
         * The Settlement action itself remains readable for Viewer because
         * the settlement endpoint is intentionally read-only.
         */
        $this->assertStringNotContainsString(
            "'[data-termination-settlement]'",
            $permissions
        );
    }

}

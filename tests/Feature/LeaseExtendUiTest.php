<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeaseExtendUiTest extends TestCase
{
    public function test_lease_ui_retires_generic_edit_and_exposes_extend(): void
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

        $this->assertStringNotContainsString(
            '[data-edit-lease]',
            $javascript
        );

        $this->assertStringNotContainsString(
            'openEditLeaseModal',
            $javascript
        );

        $this->assertStringNotContainsString(
            'leaseFormMode',
            $javascript
        );

        $this->assertStringNotContainsString(
            'editingLeaseId',
            $javascript
        );

        $this->assertStringContainsString(
            '[data-extend-lease]',
            $javascript
        );

        $this->assertStringContainsString(
            'openExtendLeaseModal',
            $javascript
        );

        $this->assertStringContainsString(
            'submitLeaseExtendForm',
            $javascript
        );

        $this->assertStringContainsString(
            '/extend',
            $javascript
        );

        $this->assertStringContainsString(
            'id="lease-extend-modal"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-effective-from"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-end-date"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-rent"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-frequency"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-due-day"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-vat-rate"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-increment-type"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-increment-value"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-next-increment-date"',
            $blade
        );

        $this->assertStringContainsString(
            'id="lease-extend-notes"',
            $blade
        );

        $this->assertStringNotContainsString(
            'lease-extend-proration',
            $blade
        );

        $this->assertStringContainsString(
            '[data-extend-lease]',
            $permissions
        );
    }

    public function test_add_lease_browser_submission_is_creation_only(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/leases.js'
                )
            );

        $start =
            strpos(
                $javascript,
                'async function submitLeaseForm'
            );

        $this->assertNotFalse(
            $start
        );

        $end =
            strpos(
                $javascript,
                'function initializeLeaseExtendDrawer',
                $start
            );

        if ($end === false) {
            $end =
                strlen(
                    $javascript
                );
        }

        $submission =
            substr(
                $javascript,
                $start,
                $end - $start
            );

        $this->assertStringContainsString(
            "'/api/leases'",
            $submission
        );

        $this->assertStringContainsString(
            "'POST'",
            $submission
        );

        $this->assertStringNotContainsString(
            "'PATCH'",
            $submission
        );
    }
}

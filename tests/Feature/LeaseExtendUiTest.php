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

    /**
     * V1.0.45: there is one way to create a letting, and it is the
     * assistant.
     *
     * This used to assert that the Add lease drawer only ever POSTed and
     * never PATCHed - that it created and could not edit. The drawer is
     * gone, so the guarantee is now structural: the Leases page carries
     * no lease form at all, and Add lease is a link to the assistant.
     *
     * Worth keeping as a test rather than as a memory. Two ways to create
     * the same record is how the two drifted apart in the first place,
     * and nothing else would notice a second one being added back.
     */
    public function test_a_lease_is_created_in_one_place_only(): void
    {
        $blade = file_get_contents(
            resource_path('views/app/leases.blade.php')
        );

        $javascript = file_get_contents(
            resource_path('js/leases.js')
        );

        $this->assertStringNotContainsString(
            'id="lease-modal"',
            $blade,
            'The Leases page must not carry a lease creation drawer.'
        );

        $this->assertStringNotContainsString(
            'id="lease-form"',
            $blade
        );

        $this->assertStringContainsString(
            'href="/leases/wizard"',
            $blade,
            'Add lease must lead to the assistant.'
        );

        $this->assertStringNotContainsString(
            'submitLeaseForm',
            $javascript,
            'The retired drawer left its submission behind.'
        );
    }
}

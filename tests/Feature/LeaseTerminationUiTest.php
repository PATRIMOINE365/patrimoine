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
}

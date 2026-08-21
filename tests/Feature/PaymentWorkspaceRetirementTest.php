<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentWorkspaceRetirementTest extends TestCase
{
    public function test_standalone_payments_page_route_is_removed(): void
    {
        $this->assertFalse(
            Route::has('payments')
        );

        $this->get('/payments')
            ->assertNotFound();
    }

    public function test_operational_navigation_has_no_payments_link(): void
    {
        $layout =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        $this->assertIsString(
            $layout
        );

        $this->assertStringNotContainsString(
            'href="/payments"',
            $layout
        );

        $this->assertStringNotContainsString(
            'request()->is(\'payments\')',
            $layout
        );
    }

    public function test_retired_payments_workspace_files_are_absent(): void
    {
        $this->assertFileDoesNotExist(
            resource_path(
                'views/app/payments.blade.php'
            )
        );

        $this->assertFileDoesNotExist(
            resource_path(
                'js/payments.js'
            )
        );
    }

    public function test_application_bundle_no_longer_initializes_payments_workspace(): void
    {
        $app =
            file_get_contents(
                resource_path(
                    'js/app.js'
                )
            );

        $this->assertIsString(
            $app
        );

        $this->assertStringNotContainsString(
            './payments.js',
            $app
        );

        $this->assertStringNotContainsString(
            'initializePayments',
            $app
        );
    }

    public function test_payment_api_and_payment_report_routes_remain_available(): void
    {
        $routes =
            collect(
                Route::getRoutes()->getRoutes()
            )
                ->map(
                    fn ($route) => implode(
                        '|',
                        $route->methods()
                    )
                        .' '
                        .$route->uri()
                );

        $this->assertTrue(
            $routes->contains(
                fn (string $route): bool => str_contains(
                    $route,
                    'api/payments'
                )
            )
        );

        $this->assertTrue(
            $routes->contains(
                fn (string $route): bool => str_contains(
                    $route,
                    'api/payments/{payment}/receipt'
                )
            )
        );

        $this->assertTrue(
            $routes->contains(
                fn (string $route): bool => str_contains(
                    $route,
                    'api/reports/payments'
                )
            )
        );
    }

    public function test_reports_workspace_still_contains_payments_report(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/reports.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'data-report-type="payments"',
            $view
        );
    }

    public function test_tenant_workspace_remains_primary_payment_workflow(): void
    {
        $tenantJs =
            file_get_contents(
                resource_path(
                    'js/tenants.js'
                )
            );

        $this->assertIsString(
            $tenantJs
        );

        $this->assertStringContainsString(
            "'/api/payments'",
            $tenantJs
        );

        $this->assertStringContainsString(
            'tenant-deposit',
            strtolower(
                $tenantJs
            )
        );
    }
}

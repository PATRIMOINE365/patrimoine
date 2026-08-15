<?php

use App\Http\Controllers\Api\ApplicationPresentationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ManagingOrganisationController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ConsumableAdvanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\OwnerExpenseController;
use App\Http\Controllers\Api\OwnerLedgerController;
use App\Http\Controllers\Api\OwnerPayoutController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RentReserveController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\SecurityDepositController;
use App\Http\Controllers\Api\TenantFundController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentRegisterController;
use App\Http\Controllers\Api\OwnerAccountController;


/*
|--------------------------------------------------------------------------
| Patrimoine API Routes
|--------------------------------------------------------------------------
|
| Authentication is provided by Laravel Sanctum.
|
| Only the login endpoint is public. All business data and financial
| operations require an authenticated Property Manager.
|
*/

/*
|--------------------------------------------------------------------------
| Application Presentation Configuration
|--------------------------------------------------------------------------
|
| Public by design so the login screen can resolve the organisation
| language and currency before authentication.
|
*/

Route::get(
    'presentation-config',
    ApplicationPresentationController::class
);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

/*
 * Login is public but rate-limited.
 */
Route::post(
    'auth/login',
    [AuthController::class, 'login']
)->middleware('throttle:5,1');

/*
 * Any authenticated Patrimoine user may inspect their identity
 * or revoke their current API token, regardless of application role.
 */
Route::middleware('auth:sanctum')->group(
    function (): void {
        Route::get(
            'auth/me',
            [AuthController::class, 'me']
        );

        Route::post(
            'auth/logout',
            [AuthController::class, 'logout']
        );
    }
);

/*
|--------------------------------------------------------------------------
| Property Manager API
|--------------------------------------------------------------------------
|
| Patrimoine 1.0 business operations are restricted to authenticated
| users with the property_manager role.
|
*/

Route::middleware([
    'auth:sanctum',
    'role:property_manager',
])->group(
    function (): void {
        /*
        |--------------------------------------------------------------------------
        | Core Domain Resources
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'parties',
            PartyController::class
        );

        Route::apiResource(
            'buildings',
            BuildingController::class
        );

        Route::apiResource(
            'units',
            UnitController::class
        );

        Route::apiResource(
            'leases',
            LeaseController::class
        );


        /*
        |--------------------------------------------------------------------------
        | Managing Organisation
        |--------------------------------------------------------------------------
        |
        | Patrimoine 1.0 is single-tenant and therefore has one application-wide
        | managing organisation used as the legal and administrative identity.
        |
        */

        Route::get(
            'managing-organisation',
            [ManagingOrganisationController::class, 'show']
        );

        Route::put(
            'managing-organisation',
            [ManagingOrganisationController::class, 'update']
        );




        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        |
        | Operational and financial dashboard metrics.
        |
        */

        Route::get(
            'dashboard',
            [DashboardController::class, 'summary']
        );

        Route::get(
            'dashboard/overdue',
            [DashboardController::class, 'overdue']
        );

        Route::get(
            'dashboard/upcoming',
            [DashboardController::class, 'upcoming']
        );

        /*
        |--------------------------------------------------------------------------
        | Financial Documents
        |--------------------------------------------------------------------------
        |
        | PDFs are rendered on demand so document-generation services can also
        | be reused for email attachments and future archival workflows.
        |
        */

        Route::get(
            'invoices/{invoice}/pdf',
            [DocumentController::class, 'invoice']
        );

        Route::get(
            'payments/{payment}/receipt',
            [DocumentController::class, 'receipt']
        );

        Route::get(
            'owner-deposits/{ownerTransaction}/receipt',
            [DocumentController::class, 'ownerDepositReceipt']
        );

        Route::get(
            'security-deposit-settlements/{settlement}/voucher',
            [DocumentController::class, 'securityDepositVoucher']
        );

                /*
        |--------------------------------------------------------------------------
        | Unified Payment Register
        |--------------------------------------------------------------------------
        |
        | Read-only operational view combining tenant Payments and incoming
        | Owner deposits without merging their underlying accounting models.
        |
        */

        Route::get(
            'payment-register',
            [PaymentRegisterController::class, 'index']
        );

        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        |
        | Payments are transactional financial records and intentionally do not
        | expose generic update or delete operations.
        |
        */

        Route::get(
            'payments',
            [PaymentController::class, 'index']
        );

        Route::post(
            'payments',
            [PaymentController::class, 'store']
        );

        Route::get(
            'payments/{payment}',
            [PaymentController::class, 'show']
        );

        /*
        |--------------------------------------------------------------------------
        | Tenant Funds
        |--------------------------------------------------------------------------
        |
        | Unallocated tenant Payment money may be classified into dedicated held
        | funds such as Rent Reserve, Consumable Advance and Security Deposit.
        |
        */

        Route::post(
            'payments/{payment}/tenant-funds',
            [TenantFundController::class, 'allocate']
        );

        /*
         * Rent Reserve may only be consumed when the Lease termination-notice
         * workflow permits it.
         */
        Route::post(
            'tenant-funds/{tenantFundAccount}/consume-rent',
            [RentReserveController::class, 'consume']
        );

        /*
         * Consumable Advance may be applied against rent during the normal
         * Lease lifecycle.
         */
        Route::post(
            'tenant-funds/{tenantFundAccount}/consume-advance',
            [ConsumableAdvanceController::class, 'consume']
        );


        /*
        * Return the current Security Deposit position and settlement preview.
        */
        Route::get(
            'leases/{lease}/security-deposit',
            [SecurityDepositController::class, 'show']
        );

        /*
        * Record one itemized final Security Deposit deduction.
        */
        Route::post(
            'leases/{lease}/security-deposit/deductions',
            [SecurityDepositController::class, 'addDeduction']
        );

        /*
         * Finalize Security Deposit deductions, tenant debt and refund.
         */
        Route::post(
            'leases/{lease}/security-deposit/settle',
            [SecurityDepositController::class, 'settle']
        );
        /*
        |--------------------------------------------------------------------------
        | Owner Accounts
        |--------------------------------------------------------------------------
        |
        | Read-only access used by the Payments workspace to resolve an Owner
        | Party to the consolidated financial account used by owner deposits.
        |
        */

        Route::get(
            'owner-accounts',
            [OwnerAccountController::class, 'index']
        );

        Route::get(
            'owner-accounts/{ownerAccount}',
            [OwnerAccountController::class, 'show']
        );
        /*
        |--------------------------------------------------------------------------
        | Owner Financial Operations
        |--------------------------------------------------------------------------
        */

        Route::post(
            'owner-expenses',
            [OwnerExpenseController::class, 'store']
        );

        Route::post(
            'owner-accounts/{ownerAccount}/deposits',
            [OwnerLedgerController::class, 'deposit']
        );

        Route::post(
            'owner-accounts/{ownerAccount}/adjustments',
            [OwnerLedgerController::class, 'adjustment']
        );

        Route::post(
            'owner-accounts/{ownerAccount}/payouts',
            [OwnerPayoutController::class, 'store']
        );

        /*
        |--------------------------------------------------------------------------
        | Financial Email Delivery
        |--------------------------------------------------------------------------
        |
        | These endpoints allow administrators to resend previously generated
        | invoices and receipts without recreating the financial records.
        |
        */

        Route::post(
            'invoices/{invoice}/send-email',
            [EmailController::class, 'invoice']
        );

        Route::post(
            'payments/{payment}/send-receipt',
            [EmailController::class, 'receipt']
        );

        /*
        |--------------------------------------------------------------------------
        | Formal Report Exports
        |--------------------------------------------------------------------------
        |
        | PDF and CSV representations reuse the same report-service calculations
        | exposed through the JSON reporting API.
        |
        */

        Route::get(
            'reports/owners/{party}/pdf',
            [ReportExportController::class, 'ownerPdf']
        );

        Route::get(
            'reports/owners/{party}/csv',
            [ReportExportController::class, 'ownerCsv']
        );

        Route::get(
            'reports/buildings/{building}/pdf',
            [ReportExportController::class, 'buildingPdf']
        );

        Route::get(
            'reports/buildings/{building}/csv',
            [ReportExportController::class, 'buildingCsv']
        );

        Route::get(
            'reports/units/{unit}/pdf',
            [ReportExportController::class, 'unitPdf']
        );

        Route::get(
            'reports/units/{unit}/csv',
            [ReportExportController::class, 'unitCsv']
        );

        Route::get(
            'reports/tenants/{party}/pdf',
            [ReportExportController::class, 'tenantPdf']
        );

        Route::get(
            'reports/tenants/{party}/csv',
            [ReportExportController::class, 'tenantCsv']
        );

        Route::get(
            'reports/managing-organisation/pdf',
            [ReportExportController::class, 'managingOrganisationPdf']
        );

        Route::get(
            'reports/managing-organisation/csv',
            [ReportExportController::class, 'managingOrganisationCsv']
        );

        /*
        |--------------------------------------------------------------------------
        | Formal Report JSON API
        |--------------------------------------------------------------------------
        |
        | These endpoints expose the read-only projections produced by the
        | report service layer.
        |
        */

        Route::get(
            'reports/owners/{party}',
            [ReportController::class, 'owner']
        );

        Route::get(
            'reports/buildings/{building}',
            [ReportController::class, 'building']
        );

        Route::get(
            'reports/units/{unit}',
            [ReportController::class, 'unit']
        );

        Route::get(
            'reports/tenants/{party}',
            [ReportController::class, 'tenant']
        );

        Route::get(
            'reports/managing-organisation',
            [ReportController::class, 'managingOrganisation']
        );
    }
);

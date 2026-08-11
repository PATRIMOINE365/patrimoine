<?php

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

/*
|--------------------------------------------------------------------------
| Patrimoine API Routes
|--------------------------------------------------------------------------
|
| Authentication middleware will be added once the initial API surface
| has been completed and tested.
|
*/

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
 * Consumable Advance may be applied against rent during the normal Lease
 * lifecycle.
 */
Route::post(
    'tenant-funds/{tenantFundAccount}/consume-advance',
    [ConsumableAdvanceController::class, 'consume']
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
| More-specific export routes are deliberately registered before the
| corresponding plain report routes.
|
*/

/*
 * Owner report exports.
 */
Route::get(
    'reports/owners/{party}/pdf',
    [ReportExportController::class, 'ownerPdf']
);

Route::get(
    'reports/owners/{party}/csv',
    [ReportExportController::class, 'ownerCsv']
);

/*
 * Building report exports.
 */
Route::get(
    'reports/buildings/{building}/pdf',
    [ReportExportController::class, 'buildingPdf']
);

Route::get(
    'reports/buildings/{building}/csv',
    [ReportExportController::class, 'buildingCsv']
);

/*
 * Unit report exports.
 */
Route::get(
    'reports/units/{unit}/pdf',
    [ReportExportController::class, 'unitPdf']
);

Route::get(
    'reports/units/{unit}/csv',
    [ReportExportController::class, 'unitCsv']
);

/*
 * Tenant statement exports.
 */
Route::get(
    'reports/tenants/{party}/pdf',
    [ReportExportController::class, 'tenantPdf']
);

Route::get(
    'reports/tenants/{party}/csv',
    [ReportExportController::class, 'tenantCsv']
);

/*
 * Managing Organisation report exports.
 */
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
| These endpoints expose the read-only projections produced by the report
| service layer.
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

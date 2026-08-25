<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ActivityLogExportController;
use App\Http\Controllers\Api\ApplicationPresentationController;
use App\Http\Controllers\Api\ArrearsReportController;
use App\Http\Controllers\Api\ArrearsReportExportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ConsumableAdvanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\FinancialJournalController;
use App\Http\Controllers\Api\FinancialJournalExportController;
use App\Http\Controllers\Api\FundsReportController;
use App\Http\Controllers\Api\FundsReportExportController;
use App\Http\Controllers\Api\InitialSetupController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\LeaseFinancialHistoryExportController;
use App\Http\Controllers\Api\ManagingOrganisationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OccupancyReportController;
use App\Http\Controllers\Api\OccupancyReportExportController;
use App\Http\Controllers\Api\OwnerAccountController;
use App\Http\Controllers\Api\OwnerExpenseBillController;
use App\Http\Controllers\Api\OwnerExpenseController;
use App\Http\Controllers\Api\OwnerLedgerController;
use App\Http\Controllers\Api\OwnerPayoutController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentRegisterController;
use App\Http\Controllers\Api\PaymentReportController;
use App\Http\Controllers\Api\PaymentReportExportController;
use App\Http\Controllers\Api\RegistryPortabilityController;
use App\Http\Controllers\Api\ReleaseLogController;
use App\Http\Controllers\Api\RentIncrementController;
use App\Http\Controllers\Api\RentReserveController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\SecurityDepositController;
use App\Http\Controllers\Api\TenantFundController;
use App\Http\Controllers\Api\TenantFundDepositController;
use App\Http\Controllers\Api\TenantFundTransferController;
use App\Http\Controllers\Api\TenantFundWithdrawalController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

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
| Initial Installation
|--------------------------------------------------------------------------
|
| A fresh Patrimoine installation has no authenticated user yet.
|
| These two endpoints are therefore public, but the POST operation becomes
| permanently unavailable as soon as an application user or configured
| Managing Organisation exists.
|
*/
Route::get(
    'setup/status',
    [InitialSetupController::class, 'status']
);

Route::post(
    'setup',
    [InitialSetupController::class, 'store']
)->middleware('throttle:5,1');

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
 * First-time password setup and Forgot Password are public but rate-limited.
 */
Route::post(
    'auth/invitations/accept',
    [PasswordController::class, 'acceptInvitation']
)->middleware('throttle:5,1');

Route::post(
    'auth/forgot-password',
    [PasswordController::class, 'forgot']
)->middleware('throttle:5,1');

Route::post(
    'auth/reset-password',
    [PasswordController::class, 'reset']
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

        Route::patch(
            'auth/me',
            [AuthController::class, 'updateMe']
        );

        Route::post(
            'auth/release-notification/read',
            [
                AuthController::class,
                'acknowledgeReleaseNotification',
            ]
        );

        /*
         * V1.0.7: localized update log, readable by every role.
         */
        Route::get(
            'release-log',
            [ReleaseLogController::class, 'index']
        );

        Route::post(
            'auth/logout',
            [AuthController::class, 'logout']
        );

        Route::post(
            'auth/change-password',
            [PasswordController::class, 'change']
        );
    }
);

/*
|--------------------------------------------------------------------------
| Authenticated Business API
|--------------------------------------------------------------------------
|
| V1.0.3 authorization is capability-based. Role names are mapped centrally
| to fixed capabilities in UserRole; routes express only the capability
| required for the operation.
|
*/

Route::middleware('auth:sanctum')->group(
    function (): void {
        /*
        |--------------------------------------------------------------------------
        | Operational Read Access
        |--------------------------------------------------------------------------
        |
        | Administrator, Property Manager and Viewer.
        |
        */

        Route::middleware('capability:view_operations')->group(
            function (): void {
                Route::get(
                    'parties',
                    [PartyController::class, 'index']
                );

                Route::get(
                    'parties/{party}',
                    [PartyController::class, 'show']
                );

                Route::get(
                    'buildings',
                    [BuildingController::class, 'index']
                );

                Route::get(
                    'buildings/{building}',
                    [BuildingController::class, 'show']
                );

                Route::get(
                    'units',
                    [UnitController::class, 'index']
                );

                Route::get(
                    'units/{unit}',
                    [UnitController::class, 'show']
                );

                Route::get(
                    'leases',
                    [LeaseController::class, 'index']
                );

                Route::get(
                    'leases/{lease}',
                    [LeaseController::class, 'show']
                );

                Route::get(
                    'leases/{lease}/financial-history',
                    [LeaseController::class, 'financialHistory']
                );

                /*
                 * V1.0.6: rent increments are readable by every role so the
                 * lease workspace (and future mobile clients) can show the
                 * increment history alongside the lease.
                 */
                Route::get(
                    'leases/{lease}/rent-increments',
                    [RentIncrementController::class, 'index']
                );

            Route::get(
                'leases/{lease}/termination-settlement',
                [LeaseController::class, 'terminationSettlement']
            );

                /*
                 * V1.0.7: derived notification center for the bell menu.
                 */
                Route::get(
                    'notifications',
                    [NotificationController::class, 'index']
                );

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

                Route::get(
                    'payment-register',
                    [PaymentRegisterController::class, 'index']
                );

                Route::get(
                    'payments',
                    [PaymentController::class, 'index']
                );

                Route::get(
                    'payments/{payment}',
                    [PaymentController::class, 'show']
                );

                Route::get(
                    'leases/{lease}/security-deposit',
                    [SecurityDepositController::class, 'show']
                );

                Route::get(
                    'owner-accounts',
                    [OwnerAccountController::class, 'index']
                );

                Route::get(
                    'owner-accounts/{ownerAccount}',
                    [OwnerAccountController::class, 'show']
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Operational Create / Modify
        |--------------------------------------------------------------------------
        |
        | Administrator and Property Manager.
        |
        */

        Route::middleware('capability:manage_operations')->group(
            function (): void {
                Route::post(
                    'parties',
                    [PartyController::class, 'store']
                );

                Route::match(
                    ['put', 'patch'],
                    'parties/{party}',
                    [PartyController::class, 'update']
                );

                Route::post(
                    'buildings',
                    [BuildingController::class, 'store']
                );

                Route::match(
                    ['put', 'patch'],
                    'buildings/{building}',
                    [BuildingController::class, 'update']
                );

                Route::post(
                    'units',
                    [UnitController::class, 'store']
                );

                Route::match(
                    ['put', 'patch'],
                    'units/{unit}',
                    [UnitController::class, 'update']
                );

                Route::post(
                    'leases',
                    [LeaseController::class, 'store']
                );

                Route::match(
                    ['put', 'patch'],
                    'leases/{lease}',
                    [LeaseController::class, 'update']
                );

                Route::post(
                    'leases/{lease}/extend',
                    [LeaseController::class, 'extend']
                );

                Route::post(
                    'leases/{lease}/termination',
                    [LeaseController::class, 'initiateTermination']
                );

                Route::post(
                    'leases/{lease}/termination/complete',
                    [LeaseController::class, 'completeTermination']
                );

                Route::post(
                    'leases/{lease}/termination/cancel',
                    [LeaseController::class, 'cancelTermination']
                );

                /*
                 * V1.0.6: schedule / cancel rent increments over the API.
                 *
                 * Applying an increment intentionally has no HTTP route —
                 * rent only ever changes through the daily
                 * patrimoine:apply-due-rent-increments scheduler command.
                 */
                Route::post(
                    'leases/{lease}/rent-increments',
                    [RentIncrementController::class, 'store']
                );

                Route::post(
                    'rent-increments/{rentIncrement}/cancel',
                    [RentIncrementController::class, 'cancel']
                );

                /*
                 * V1.0.5 Lease Delete is a specific lifecycle exception to
                 * the general Administrator-only business-delete rule.
                 *
                 * Administrator + Property Manager may preview/execute it;
                 * Viewer remains read-only.
                 */
                Route::get(
                    'leases/{lease}/deletion-impact',
                    [LeaseController::class, 'deletionImpact']
                );

                Route::delete(
                    'leases/{lease}',
                    [LeaseController::class, 'destroy']
                );

                /*
                 * Resending an existing business document is an explicit
                 * operational action and is therefore unavailable to Viewer.
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
                 * V1.0.7: resend an owner expense bill to the billed owner.
                 */
                Route::post(
                    'owner-expense-bills/{ownerExpenseBill}/send-email',
                    [OwnerExpenseBillController::class, 'sendEmail']
                );

                /*
                 * V1.0.8: resend a tenant fund Transfer voucher to the
                 * tenant. Follows the invoice/receipt resend rule above.
                 */
                Route::post(
                    'tenant-fund-transfers/{tenantFundTransaction}/send-email',
                    [TenantFundTransferController::class, 'sendEmail']
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Administrator-Only Business Deletion
        |--------------------------------------------------------------------------
        |
        | Capability grants permission to attempt deletion. Existing database
        | and business-integrity constraints remain authoritative.
        |
        */

        Route::middleware('capability:delete_records')->group(
            function (): void {
                Route::delete(
                    'parties/{party}',
                    [PartyController::class, 'destroy']
                );

                Route::delete(
                    'buildings/{building}',
                    [BuildingController::class, 'destroy']
                );

                Route::delete(
                    'units/{unit}',
                    [UnitController::class, 'destroy']
                );

            }
        );

        /*
        |--------------------------------------------------------------------------
        | Manual Financial Operations
        |--------------------------------------------------------------------------
        |
        | Administrator and Property Manager. Viewer is strictly read-only.
        |
        */

        Route::middleware('capability:manage_finance')->group(
            function (): void {
                Route::post(
                    'payments',
                    [PaymentController::class, 'store']
                );

                Route::post(
                    'payments/{payment}/tenant-funds',
                    [TenantFundController::class, 'allocate']
                );

                Route::post(
                    'tenant-fund-deposits',
                    [TenantFundDepositController::class, 'store']
                );

                Route::post(
                    'tenant-fund-withdrawals',
                    [TenantFundWithdrawalController::class, 'store']
                );

                Route::get(
                    'withdrawal-receipts/{withdrawalReceipt}/pdf',
                    [DocumentController::class, 'withdrawalReceipt']
                );

                Route::post(
                    'tenant-funds/{tenantFundAccount}/adjustments',
                    [TenantFundController::class, 'adjustment']
                );

                /*
                 * V1.0.7: move held money between two fund accounts of
                 * the SAME tenant, possibly across that tenant's Leases.
                 */
                Route::post(
                    'tenant-funds/transfers',
                    [TenantFundTransferController::class, 'store']
                );

                Route::post(
                    'tenant-funds/{tenantFundAccount}/consume-rent',
                    [RentReserveController::class, 'consume']
                );

                Route::post(
                    'tenant-funds/{tenantFundAccount}/consume-advance',
                    [ConsumableAdvanceController::class, 'consume']
                );

                Route::post(
                    'leases/{lease}/security-deposit/deductions',
                    [SecurityDepositController::class, 'addDeduction']
                );

                Route::post(
                    'leases/{lease}/security-deposit/settle',
                    [SecurityDepositController::class, 'settle']
                );

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
                 * V1.0.7: bill multiple expense lines directly to one owner.
                 */
                Route::post(
                    'owner-accounts/{ownerAccount}/expense-bills',
                    [OwnerExpenseBillController::class, 'store']
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Reports, Statements, Documents and Exports
        |--------------------------------------------------------------------------
        |
        | All three roles may access applicable reports/statements and their
        | existing PDF/CSV/document representations.
        |
        */

        Route::middleware('capability:export_reports')->group(
            function (): void {
                Route::get(
                    'leases/{lease}/termination-notice/pdf',
                    [DocumentController::class, 'terminationNotice']
                );

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

                /*
                 * V1.0.7: itemized owner expense bill PDF.
                 */
                Route::get(
                    'owner-expense-bills/{ownerExpenseBill}/pdf',
                    [OwnerExpenseBillController::class, 'pdf']
                );

                /*
                 * V1.0.7: owner payout receipt PDF.
                 */
                Route::get(
                    'owner-payouts/{ownerPayout}/receipt',
                    [DocumentController::class, 'payoutReceipt']
                );

                Route::get(
                    'adjustment-vouchers/{adjustmentVoucher}/pdf',
                    [DocumentController::class, 'adjustmentVoucher']
                );

                /*
                 * V1.0.7: Transfer voucher PDF, keyed by the debit leg
                 * of the Tenant fund Transfer.
                 */
                Route::get(
                    'tenant-fund-transfers/{tenantFundTransaction}/voucher',
                    [TenantFundTransferController::class, 'voucher']
                );

                Route::get(
                    'security-deposit-settlements/{settlement}/voucher',
                    [DocumentController::class, 'securityDepositVoucher']
                );

                Route::get(
                    'leases/{lease}/financial-history/pdf',
                    [LeaseFinancialHistoryExportController::class, 'pdf']
                );

                Route::get(
                    'leases/{lease}/financial-history/csv',
                    [LeaseFinancialHistoryExportController::class, 'csv']
                );

                Route::get(
                    'leases/{lease}/financial-history/xlsx',
                    [LeaseFinancialHistoryExportController::class, 'xlsx']
                );

                Route::get(
                    'reports/payments/pdf',
                    [PaymentReportExportController::class, 'pdf']
                );

                Route::get(
                    'reports/payments/csv',
                    [PaymentReportExportController::class, 'csv']
                );

                Route::get(
                    'reports/payments/xlsx',
                    [PaymentReportExportController::class, 'xlsx']
                );

                /*
                 * V1.0.7 report subjects: portfolio Occupancy, tenant
                 * Arrears Aging and Funds Held snapshots, each with the
                 * standard PDF/CSV/XLSX download rule.
                 */
                Route::get(
                    'reports/occupancy/pdf',
                    [OccupancyReportExportController::class, 'pdf']
                );

                Route::get(
                    'reports/occupancy/csv',
                    [OccupancyReportExportController::class, 'csv']
                );

                Route::get(
                    'reports/occupancy/xlsx',
                    [OccupancyReportExportController::class, 'xlsx']
                );

                Route::get(
                    'reports/arrears/pdf',
                    [ArrearsReportExportController::class, 'pdf']
                );

                Route::get(
                    'reports/arrears/csv',
                    [ArrearsReportExportController::class, 'csv']
                );

                Route::get(
                    'reports/arrears/xlsx',
                    [ArrearsReportExportController::class, 'xlsx']
                );

                Route::get(
                    'reports/funds/pdf',
                    [FundsReportExportController::class, 'pdf']
                );

                Route::get(
                    'reports/funds/csv',
                    [FundsReportExportController::class, 'csv']
                );

                Route::get(
                    'reports/funds/xlsx',
                    [FundsReportExportController::class, 'xlsx']
                );

                Route::get(
                    'reports/owners/{party}/pdf',
                    [ReportExportController::class, 'ownerPdf']
                );

                Route::get(
                    'reports/owners/{party}/csv',
                    [ReportExportController::class, 'ownerCsv']
                );

                Route::get(
                    'reports/owners/{party}/xlsx',
                    [ReportExportController::class, 'ownerXlsx']
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
                    'reports/buildings/{building}/xlsx',
                    [ReportExportController::class, 'buildingXlsx']
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
                    'reports/units/{unit}/xlsx',
                    [ReportExportController::class, 'unitXlsx']
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
                    'reports/tenants/{party}/xlsx',
                    [ReportExportController::class, 'tenantXlsx']
                );

                Route::get(
                    'reports/managing-organisation/pdf',
                    [ReportExportController::class, 'managingOrganisationPdf']
                );

                Route::get(
                    'reports/managing-organisation/csv',
                    [ReportExportController::class, 'managingOrganisationCsv']
                );

                Route::get(
                    'reports/managing-organisation/xlsx',
                    [ReportExportController::class, 'managingOrganisationXlsx']
                );

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

                Route::get(
                    'reports/payments',
                    PaymentReportController::class
                );

                /*
                 * V1.0.7 report subject data endpoints.
                 *
                 * Occupancy and Arrears accept an optional as_of=Y-m-d
                 * snapshot date; Funds Held is always as of today.
                 */
                Route::get(
                    'reports/occupancy',
                    OccupancyReportController::class
                );

                Route::get(
                    'reports/arrears',
                    ArrearsReportController::class
                );

                Route::get(
                    'reports/funds',
                    FundsReportController::class
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Financial Journal
        |--------------------------------------------------------------------------
        |
        | Administrator-only immutable accounting history.
        |
        | Reading, searching and filtering Journal entries are passive
        | operations. No write, edit, reversal or delete routes exist here.
        |
        */

        Route::middleware('capability:view_financial_journal')->group(
            function (): void {
                Route::get(
                    'financial-journal',
                    [FinancialJournalController::class, 'index']
                );

                Route::get(
                    'financial-journal/filter-options',
                    [FinancialJournalController::class, 'filterOptions']
                );

                Route::get(
                    'financial-journal/pdf',
                    [FinancialJournalExportController::class, 'pdf']
                );

                Route::get(
                    'financial-journal/csv',
                    [FinancialJournalExportController::class, 'csv']
                );

                Route::get(
                    'financial-journal/xlsx',
                    [FinancialJournalExportController::class, 'xlsx']
                );

                Route::get(
                    'financial-journal/{journalEntry}',
                    [FinancialJournalController::class, 'show']
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        |
        | Administrator-only immutable historical audit access.
        |
        | Reading, searching and filtering Activity Log events are passive
        | operations and therefore do not create additional Activity Log events.
        | No write/delete routes exist.
        |
        */

        Route::middleware('capability:view_activity_log')->group(
            function (): void {
                Route::get(
                    'activity-log',
                    [ActivityLogController::class, 'index']
                );

                Route::get(
                    'activity-log/pdf',
                    [ActivityLogExportController::class, 'pdf']
                );

                Route::get(
                    'activity-log/csv',
                    [ActivityLogExportController::class, 'csv']
                );

                // V1.0.7: XLSX completes the PDF/XLSX/CSV download rule.
                Route::get(
                    'activity-log/xlsx',
                    [ActivityLogExportController::class, 'xlsx']
                );

                Route::get(
                    'activity-log/{activityLog}',
                    [ActivityLogController::class, 'show']
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        |
        | Administrator only. User administration is intentionally separate
        | from domain Party management.
        |
        */

        Route::middleware('capability:manage_users')->group(
            function (): void {
                Route::get(
                    'users',
                    [UserController::class, 'index']
                );

                Route::post(
                    'users',
                    [UserController::class, 'store']
                );

                Route::get(
                    'users/{user}',
                    [UserController::class, 'show']
                );

                Route::match(
                    ['put', 'patch'],
                    'users/{user}',
                    [UserController::class, 'update']
                );

                Route::delete(
                    'users/{user}',
                    [UserController::class, 'destroy']
                );

                Route::post(
                    'users/{user}/resend-invitation',
                    [UserController::class, 'resendInvitation']
                );

                Route::post(
                    'users/{user}/password-reset',
                    [UserController::class, 'initiatePasswordReset']
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Managing Organisation Settings
        |--------------------------------------------------------------------------
        |
        | Settings are Administrator-only, including reading the settings
        | record itself.
        |
        */

        Route::middleware('capability:manage_settings')->group(
            function (): void {
                Route::get(
                    'managing-organisation',
                    [ManagingOrganisationController::class, 'show']
                );

                Route::put(
                    'managing-organisation',
                    [ManagingOrganisationController::class, 'update']
                );

                /*
                 * V1.0.7 Registry backup & idempotent restore.
                 *
                 * Administrator-only, like the rest of the Settings area.
                 * Only Registry data (parties, buildings + ownership,
                 * units, leases) is portable; financial history is
                 * immutable and intentionally has no import route.
                 */
                Route::get(
                    'registry/export',
                    [RegistryPortabilityController::class, 'export']
                );

                Route::get(
                    'registry/export/full',
                    [RegistryPortabilityController::class, 'exportFull']
                );

                Route::get(
                    'registry/export/pdf',
                    [RegistryPortabilityController::class, 'exportPdf']
                );

                Route::post(
                    'registry/import',
                    [RegistryPortabilityController::class, 'import']
                );

                /*
                 * Full multi-sheet restore in one request so old→new id
                 * remapping carries across the four entities.
                 */
                Route::post(
                    'registry/import/full',
                    [RegistryPortabilityController::class, 'importFull']
                );
            }
        );
    }
);

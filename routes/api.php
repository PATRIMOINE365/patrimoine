<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ApplicationPresentationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ConsumableAdvanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\InitialSetupController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\ManagingOrganisationController;
use App\Http\Controllers\Api\OwnerAccountController;
use App\Http\Controllers\Api\OwnerExpenseController;
use App\Http\Controllers\Api\OwnerLedgerController;
use App\Http\Controllers\Api\OwnerPayoutController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentRegisterController;
use App\Http\Controllers\Api\RentReserveController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\SecurityDepositController;
use App\Http\Controllers\Api\TenantFundController;
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

                Route::delete(
                    'leases/{lease}',
                    [LeaseController::class, 'destroy']
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
            }
        );
    }
);

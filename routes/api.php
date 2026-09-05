<?php

use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\Admin\AdminActivityController;
use App\Http\Controllers\Api\Admin\AdminEmailController;
use App\Http\Controllers\Api\Admin\AdminLeaseController;
use App\Http\Controllers\Api\Admin\AdminOrganisationDataController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminLicenseController;
use App\Http\Controllers\Api\Admin\AdminOrganisationController;
use App\Http\Controllers\Api\Admin\AdminOrganisationDeletionController;
use App\Http\Controllers\Api\Admin\AdminOrganisationStatusController;
use App\Http\Controllers\Api\Admin\AdminReleaseLogController;
use App\Http\Controllers\Api\Admin\AdminSupportController;
use App\Http\Controllers\Api\ActivityLogExportController;
use App\Http\Controllers\Api\ApplicationPresentationController;
use App\Http\Controllers\Api\ArrearsReportController;
use App\Http\Controllers\Api\ArrearsReportExportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\ConsumableAdvanceController;
use App\Http\Controllers\Api\ClientConfigController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentLinkController;
use App\Http\Controllers\Api\EmailChangeController;
use App\Http\Controllers\Api\InvoiceAccountPaymentController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\FinancialJournalController;
use App\Http\Controllers\Api\FinancialJournalExportController;
use App\Http\Controllers\Api\FundsReportController;
use App\Http\Controllers\Api\FundsReportExportController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\LeaseWizardController;
use App\Http\Controllers\Api\LeaseWizardDraftController;
use App\Http\Controllers\Api\LeaseFinancialHistoryExportController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\ManagingOrganisationController;
use App\Http\Controllers\Api\OrganisationDeletionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OccupancyReportController;
use App\Http\Controllers\Api\OccupancyReportExportController;
use App\Http\Controllers\Api\OwnerAccountController;
use App\Http\Controllers\Api\OwnerExpenseBillController;
use App\Http\Controllers\Api\OwnerExpenseBillPaymentController;
use App\Http\Controllers\Api\OwnerExpenseController;
use App\Http\Controllers\Api\OwnerLedgerController;
use App\Http\Controllers\Api\OwnerPayoutController;
use App\Http\Controllers\Api\OwnerReserveTransferController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ProfilePhotoController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PersonalDataController;
use App\Http\Controllers\Api\PaymentRegisterController;
use App\Http\Controllers\Api\PaymentReportController;
use App\Http\Controllers\Api\PaymentReportExportController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\RegistryPortabilityController;
use App\Http\Controllers\Api\ErrorCodeController;
use App\Http\Controllers\Api\GuideController;
use App\Http\Controllers\Api\ReleaseLogController;
use App\Http\Controllers\Api\RentIncrementController;
use App\Http\Controllers\Api\RentReserveController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\SecurityDepositController;
use App\Http\Controllers\Api\SupportMessageController;
use App\Http\Controllers\Api\TenantExpenseInvoiceController;
use App\Http\Controllers\Api\TenantFundController;
use App\Http\Controllers\Api\TenantFundDepositController;
use App\Http\Controllers\Api\TenantFundExpenseController;
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
| Client Configuration (V1.0.44)
|--------------------------------------------------------------------------
|
| The first call an installed application makes, before it shows
| anything. It answers what the minimum runnable version is, whether the
| service is open, which API version to speak and where the web journeys
| live.
|
| An installed application cannot be recalled, so the moment it starts is
| the only moment it can be told it must not run. That is why this exists
| now rather than in the release where it is first needed.
|
| Public, like presentation-config beside it: nobody has signed in yet.
|
*/
Route::get(
    'config',
    ClientConfigController::class
);

/*
|--------------------------------------------------------------------------
| Public Signup (V1.0.10)
|--------------------------------------------------------------------------
|
| Multi-tenant self-service registration replaces the retired one-time
| setup wizard. Each signup provisions a fully isolated organisation.
|
*/
/*
 * V1.0.50: every throttle names its own bucket.
 *
 * Laravel keys a bare `throttle:N,M` on the caller alone — one bucket per
 * signed-in user, one per IP for guests — and the route plays no part in
 * the key. Fifteen limits were therefore one shared counter: a support
 * message (five an hour) started an hour-long window in which changing
 * your email answered "Too Many Attempts", and five sign-in attempts from
 * an office connection locked everyone behind it out of password reset.
 * The third parameter is the prefix that keeps the buckets apart.
 */
Route::post(
    'auth/register',
    [RegistrationController::class, 'register']
)->middleware('throttle:5,1,register');

Route::post(
    'auth/verify-email',
    [RegistrationController::class, 'verifyEmail']
)->middleware('throttle:10,1,verify-email');

Route::post(
    'auth/resend-verification',
    [RegistrationController::class, 'resendVerification']
)->middleware('throttle:3,1,resend-verification');

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
)->middleware('throttle:5,1,login');

/*
 * V1.0.10: second factor of every sign-in. Verification is more
 * generously throttled than login because each attempt already costs a
 * challenge attempt; resend is tight because each call sends mail.
 */
Route::post(
    'auth/mfa/verify',
    [AuthController::class, 'mfaVerify']
)->middleware('throttle:10,1,mfa-verify');

Route::post(
    'auth/mfa/resend',
    [AuthController::class, 'mfaResend']
)->middleware('throttle:3,1,mfa-resend');

/*
 * First-time password setup and Forgot Password are public but rate-limited.
 */
Route::post(
    'auth/invitations/accept',
    [PasswordController::class, 'acceptInvitation']
)->middleware('throttle:5,1,accept-invitation');

Route::post(
    'auth/forgot-password',
    [PasswordController::class, 'forgot']
)->middleware('throttle:5,1,forgot-password');

Route::post(
    'auth/reset-password',
    [PasswordController::class, 'reset']
)->middleware('throttle:5,1,reset-password');

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

        /*
         * V1.0.11: profile photo — uploaded bytes are re-encoded
         * server-side and stored on the user row.
         */
        Route::post(
            'auth/me/avatar',
            [ProfilePhotoController::class, 'store']
        );

        /*
         * V1.0.31: the picture behind the photo, so its owner can reframe
         * it. Only ever their own — there is no identifier to change.
         */
        Route::get(
            'auth/me/avatar/source',
            [ProfilePhotoController::class, 'source']
        );

        Route::delete(
            'auth/me/avatar',
            [ProfilePhotoController::class, 'destroy']
        );

        /*
         * V1.0.48: the sign-in email changes ONLY through this three-step
         * flow — password, then the current mailbox's code, then the new
         * mailbox's. PATCH auth/me refuses a changed email outright.
         *
         * Initiation is throttled tightly on its own: every request mails
         * the account's current mailbox, and without a lid that is a
         * mail cannon pointed at somebody's inbox.
         */
        Route::get(
            'auth/email-change',
            [EmailChangeController::class, 'show']
        );

        Route::post(
            'auth/email-change',
            [EmailChangeController::class, 'store']
        )->middleware('throttle:3,10,email-change');

        Route::post(
            'auth/email-change/verify-current',
            [EmailChangeController::class, 'verifyCurrent']
        )->middleware('throttle:10,1,email-change-verify-current');

        Route::post(
            'auth/email-change/verify-new',
            [EmailChangeController::class, 'verifyProposed']
        )->middleware('throttle:10,1,email-change-verify-new');

        Route::post(
            'auth/email-change/resend',
            [EmailChangeController::class, 'resend']
        )->middleware('throttle:3,1,email-change-resend');

        Route::delete(
            'auth/email-change',
            [EmailChangeController::class, 'destroy']
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

        /*
         * V1.0.30: the error catalogue, for the Error codes tab in Help.
         * The same words as the public page at /errors, in the
         * organisation's language. Readable by every role — anybody can
         * meet an error.
         */
        Route::get(
            'error-codes',
            ErrorCodeController::class
        );

        /*
         * V1.0.33: the how-to guide. Reference material like the error
         * catalogue beside it, and open to every role for the same reason:
         * somebody who may not perform a task still needs to read how it
         * is performed.
         */
        Route::get(
            'guide',
            GuideController::class
        );

        /*
         * V1.0.36: writing to support. Open to every role — being unable
         * to do your work is exactly what a Viewer needs to report.
         *
         * Throttled at five an hour per signed-in user: enough for a bad
         * morning, not enough to turn the support mailbox into a target.
         */
        Route::post(
            'support-messages',
            [SupportMessageController::class, 'store']
        )->middleware('throttle:5,60,support-messages');

        /*
         * V1.0.34: your own data, on request, by you.
         *
         * No capability gate. Asking what is held about you is not an
         * administrative act, and making somebody ask their administrator
         * for their own account data would be the wrong answer to the
         * question this route exists to answer.
         */
        Route::get(
            'auth/me/data',
            [PersonalDataController::class, 'me']
        );

        /*
         * V1.0.10: the organisation's plan, usage and the plan matrix.
         * Readable by every authenticated role; the licence page uses it.
         */
        Route::get(
            'license',
            [LicenseController::class, 'show']
        );

        Route::post(
            'auth/confirm-password',
            [AuthController::class, 'confirmPassword']
        )->middleware('throttle:5,1,confirm-password');

        Route::post(
            'auth/logout',
            [AuthController::class, 'logout']
        );

        /*
         * V1.0.44: the devices signed in to this account.
         *
         * A token that outlives the tab it was minted in is a credential
         * sitting on a physical object, and physical objects are lost,
         * sold and handed on. Somebody has to be able to see the list
         * and take an entry out of it, in their own hands, at the moment
         * they realise.
         *
         * Every route is the acting user's own account. No capability
         * gate: reading and revoking your own sessions is not an
         * administrative act, and asking an administrator to do it for
         * you is the wrong answer to "I left my phone in a taxi".
         */
        Route::get(
            'auth/devices',
            [DeviceController::class, 'index']
        );

        Route::delete(
            'auth/devices',
            [DeviceController::class, 'destroyOthers']
        );

        Route::delete(
            'auth/devices/{device}',
            [DeviceController::class, 'destroy']
        )->whereNumber('device');

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

                /*
                 * V1.0.29 guided lease creation. One submission carries
                 * the property, the owners, the tenant, the agent and
                 * the lease, and they are created in one transaction or
                 * not at all.
                 */
                Route::post(
                    'lease-wizard',
                    [LeaseWizardController::class, 'store']
                );

                /*
                 * V1.0.31: an assistant abandoned before it has a unit
                 * and a tenant cannot be saved as a lease, because a
                 * lease needs both. It is saved as itself instead, and
                 * resumed -- or discarded -- from the Leases page.
                 */
                Route::get(
                    'lease-wizard/drafts',
                    [LeaseWizardDraftController::class, 'index']
                );

                Route::post(
                    'lease-wizard/drafts',
                    [LeaseWizardDraftController::class, 'store']
                );

                Route::get(
                    'lease-wizard/drafts/{draft}',
                    [LeaseWizardDraftController::class, 'show']
                );

                Route::delete(
                    'lease-wizard/drafts/{draft}',
                    [LeaseWizardDraftController::class, 'destroy']
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
                    'tenant-fund-expenses/{tenantFundTransaction}/send-email',
                    [TenantFundExpenseController::class, 'sendEmail']
                );

                Route::post(
                    'owner-reserve-transfers/{ownerTransaction}/send-email',
                    [OwnerReserveTransferController::class, 'sendEmail']
                );

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

                /*
                 * V1.0.42: archiving.
                 *
                 * A record Patrimoine will not delete because the
                 * accounting refers to it can instead be put out of the
                 * way. It is the alternative to deletion, so it sits under
                 * the same capability: whoever may remove a record may
                 * decide to stop showing it.
                 */
                Route::post(
                    'archive/{kind}/{id}',
                    [ArchiveController::class, 'store']
                )->whereNumber('id');

            }
        );

        /*
        |--------------------------------------------------------------------------
        | The archive
        |--------------------------------------------------------------------------
        |
        | Reading it is ordinary work. Putting something back is not: it
        | returns a record to every list and every picker in the product,
        | so it is an administrator's decision.
        |
        */

        Route::get(
            'archive',
            [ArchiveController::class, 'index']
        );

        Route::middleware('capability:manage_settings')->group(
            function (): void {
                Route::delete(
                    'archive/{kind}/{id}',
                    [ArchiveController::class, 'destroy']
                )->whereNumber('id');
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

                /*
                 * V1.0.8: a tenant expense is recorded as an unpaid
                 * EXP- Invoice; settlement happens later through the
                 * Invoice account-payment flow below. The historical
                 * tenant-fund-expenses voucher/resend endpoints remain
                 * for documents recorded under the old model.
                 */
                Route::post(
                    'tenant-expense-invoices',
                    [TenantExpenseInvoiceController::class, 'store']
                );

                /*
                 * V1.0.8: pay an Invoice (rent or expense) from a
                 * tenant fund account, and cancel such a payment.
                 */
                Route::post(
                    'invoices/{invoice}/account-payments',
                    [InvoiceAccountPaymentController::class, 'store']
                );

                Route::post(
                    'invoice-account-payments/{tenantFundTransaction}/cancel',
                    [InvoiceAccountPaymentController::class, 'cancel']
                );

                Route::post(
                    'tenant-fund-withdrawals',
                    [TenantFundWithdrawalController::class, 'store']
                );

                Route::get(
                    'withdrawal-receipts/{withdrawalReceipt}/pdf',
                    [DocumentController::class, 'withdrawalReceipt']
                )->middleware('document.signed');

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
                /*
                 * V1.0.8: transfers between the owner's Payout account
                 * and Deposit/Expense account.
                 */
                Route::get(
                    'owner-accounts/{ownerAccount}/reserve-transfers',
                    [OwnerReserveTransferController::class, 'index']
                );

                Route::post(
                    'owner-accounts/{ownerAccount}/reserve-transfers',
                    [OwnerReserveTransferController::class, 'store']
                );

                Route::post(
                    'owner-accounts/{ownerAccount}/expense-bills',
                    [OwnerExpenseBillController::class, 'store']
                );

                /*
                 * V1.0.8: bills stay unpaid until explicitly settled;
                 * these endpoints list them with derived payment state,
                 * pay them from a chosen owner account side, and cancel
                 * such payments again.
                 */
                Route::get(
                    'owner-accounts/{ownerAccount}/expense-bills',
                    [OwnerExpenseBillController::class, 'index']
                );

                Route::post(
                    'owner-expense-bills/{ownerExpenseBill}/payments',
                    [OwnerExpenseBillPaymentController::class, 'store']
                );

                Route::post(
                    'owner-expense-bill-payments/{ownerTransaction}/cancel',
                    [OwnerExpenseBillPaymentController::class, 'cancel']
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

        /*
         * V1.0.8: exchange an authenticated request for a short-lived
         * signed URL so a browser tab can open a PDF document directly.
         * Authorization stays with each document route's own capability
         * middleware, which also runs for signed requests.
         */
        Route::post(
            'document-links',
            [DocumentLinkController::class, 'store']
        );

        Route::middleware([
            'document.signed',
            'capability:export_reports',
        ])->group(
            function (): void {
                Route::get(
                    'leases/{lease}/termination-notice/pdf',
                    [DocumentController::class, 'terminationNotice']
                );

                Route::get(
                    'invoices/{invoice}/pdf',
                    [DocumentController::class, 'invoice']
                );

                /*
                 * V1.0.8: receipt for an Invoice's fund-account payments.
                 */
                Route::get(
                    'invoices/{invoice}/payment-receipt',
                    [DocumentController::class, 'invoicePaymentReceipt']
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
                 * V1.0.8: receipt for an expense bill's payments.
                 */
                Route::get(
                    'owner-expense-bills/{ownerExpenseBill}/payment-receipt',
                    [OwnerExpenseBillController::class, 'paymentReceipt']
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
                    'tenant-fund-expenses/{tenantFundTransaction}/voucher',
                    [TenantFundExpenseController::class, 'voucher']
                );

                Route::get(
                    'owner-reserve-transfers/{ownerTransaction}/voucher',
                    [OwnerReserveTransferController::class, 'voucher']
                );

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
                )->middleware('license:exports');

                Route::get(
                    'reports/payments/csv',
                    [PaymentReportExportController::class, 'csv']
                )->middleware('license:exports');

                Route::get(
                    'reports/payments/xlsx',
                    [PaymentReportExportController::class, 'xlsx']
                )->middleware('license:exports');

                /*
                 * V1.0.7 report subjects: portfolio Occupancy, tenant
                 * Arrears Aging and Funds Held snapshots, each with the
                 * standard PDF/CSV/XLSX download rule.
                 */
                Route::get(
                    'reports/occupancy/pdf',
                    [OccupancyReportExportController::class, 'pdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/occupancy/csv',
                    [OccupancyReportExportController::class, 'csv']
                )->middleware('license:exports');

                Route::get(
                    'reports/occupancy/xlsx',
                    [OccupancyReportExportController::class, 'xlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/arrears/pdf',
                    [ArrearsReportExportController::class, 'pdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/arrears/csv',
                    [ArrearsReportExportController::class, 'csv']
                )->middleware('license:exports');

                Route::get(
                    'reports/arrears/xlsx',
                    [ArrearsReportExportController::class, 'xlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/funds/pdf',
                    [FundsReportExportController::class, 'pdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/funds/csv',
                    [FundsReportExportController::class, 'csv']
                )->middleware('license:exports');

                Route::get(
                    'reports/funds/xlsx',
                    [FundsReportExportController::class, 'xlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/owners/{party}/pdf',
                    [ReportExportController::class, 'ownerPdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/owners/{party}/csv',
                    [ReportExportController::class, 'ownerCsv']
                )->middleware('license:exports');

                Route::get(
                    'reports/owners/{party}/xlsx',
                    [ReportExportController::class, 'ownerXlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/buildings/{building}/pdf',
                    [ReportExportController::class, 'buildingPdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/buildings/{building}/csv',
                    [ReportExportController::class, 'buildingCsv']
                )->middleware('license:exports');

                Route::get(
                    'reports/buildings/{building}/xlsx',
                    [ReportExportController::class, 'buildingXlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/units/{unit}/pdf',
                    [ReportExportController::class, 'unitPdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/units/{unit}/csv',
                    [ReportExportController::class, 'unitCsv']
                )->middleware('license:exports');

                Route::get(
                    'reports/units/{unit}/xlsx',
                    [ReportExportController::class, 'unitXlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/tenants/{party}/pdf',
                    [ReportExportController::class, 'tenantPdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/tenants/{party}/csv',
                    [ReportExportController::class, 'tenantCsv']
                )->middleware('license:exports');

                Route::get(
                    'reports/tenants/{party}/xlsx',
                    [ReportExportController::class, 'tenantXlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/managing-organisation/pdf',
                    [ReportExportController::class, 'managingOrganisationPdf']
                )->middleware('license:exports');

                Route::get(
                    'reports/managing-organisation/csv',
                    [ReportExportController::class, 'managingOrganisationCsv']
                )->middleware('license:exports');

                Route::get(
                    'reports/managing-organisation/xlsx',
                    [ReportExportController::class, 'managingOrganisationXlsx']
                )->middleware('license:exports');

                Route::get(
                    'reports/owners/{party}',
                    [ReportController::class, 'owner']
                )->middleware('license:reports');

                Route::get(
                    'reports/buildings/{building}',
                    [ReportController::class, 'building']
                )->middleware('license:reports');

                Route::get(
                    'reports/units/{unit}',
                    [ReportController::class, 'unit']
                )->middleware('license:reports');

                Route::get(
                    'reports/tenants/{party}',
                    [ReportController::class, 'tenant']
                )->middleware('license:reports');

                Route::get(
                    'reports/managing-organisation',
                    [ReportController::class, 'managingOrganisation']
                )->middleware('license:reports');

                Route::get(
                    'reports/payments',
                    PaymentReportController::class
                )->middleware('license:reports');

                /*
                 * V1.0.7 report subject data endpoints.
                 *
                 * Occupancy and Arrears accept an optional as_of=Y-m-d
                 * snapshot date; Funds Held is always as of today.
                 */
                Route::get(
                    'reports/occupancy',
                    OccupancyReportController::class
                )->middleware('license:reports');

                Route::get(
                    'reports/arrears',
                    ArrearsReportController::class
                )->middleware('license:reports');

                Route::get(
                    'reports/funds',
                    FundsReportController::class
                )->middleware('license:reports');
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
                /*
                 * The managing organisation's own accounting: fee income
                 * earned and VAT charged on those fees. It reads the same
                 * ledger the Financial Journal does, so it sits behind the
                 * same capability.
                 */
                Route::get(
                    'accounting/summary',
                    [AccountingController::class, 'summary']
                );

                Route::get(
                    'financial-journal',
                    [FinancialJournalController::class, 'index']
                );

                Route::get(
                    'financial-journal/filter-options',
                    [FinancialJournalController::class, 'filterOptions']
                );

                /*
                 * V1.0.35: no PDF here either. dompdf holds the whole
                 * document in memory and this rendered every matching
                 * entry: 276 entries needed 456 MB against the 128 MB the
                 * live box allows, and a one-month filter did not fit
                 * either. CSV and XLSX stream and carry the same columns.
                 */

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

                /*
                 * V1.0.35: no PDF here. dompdf holds the whole document in
                 * memory and the Activity Log is kept indefinitely, so the
                 * export grew until it exhausted the memory limit and
                 * answered 500 — measured at 32 rows using 102 MB of the
                 * 128 MB available on the live box. CSV and XLSX stream and
                 * carry exactly the same columns.
                 */

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
                 * V1.0.32: closing your own account. The most destructive
                 * thing a customer can do, and the only route in the
                 * application that removes an organisation. Guarded in the
                 * controller by the typed name and the caller's password.
                 */
                Route::delete(
                    'organisation',
                    [OrganisationDeletionController::class, 'destroy']
                );

                /*
                 * V1.0.34: answering a subject access request.
                 *
                 * The organisation is the controller for its tenants and
                 * owners, so producing one person's data — and erasing
                 * them — belongs to its administrator, not to us.
                 */
                Route::get(
                    'organisation/data',
                    [PersonalDataController::class, 'organisation']
                );

                Route::get(
                    'parties/{party}/data',
                    [PersonalDataController::class, 'party']
                );

                Route::post(
                    'parties/{party}/erase',
                    [PersonalDataController::class, 'erase']
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
                )->middleware('document.signed');

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

/*
|--------------------------------------------------------------------------
| Platform Administration Console (V1.0.11)
|--------------------------------------------------------------------------
|
| Kality Ltd staff only: verified @patrimoine365.com accounts inside
| the internal platform organisation. Reads span every customer
| organisation; every mutation is written to both audit trails.
|
*/

Route::middleware(['auth:sanctum', 'platform.admin'])
    ->prefix('admin')
    ->group(function (): void {
        Route::get(
            'dashboard',
            AdminDashboardController::class
        );

        Route::get(
            'activity',
            [AdminActivityController::class, 'index']
        );

        /*
         * V1.0.31: the release-by-release history. Customers read a
         * shortened log in Help; support reads this one.
         */
        Route::get(
            'release-log',
            AdminReleaseLogController::class
        );

        Route::get(
            'organisations',
            [AdminOrganisationController::class, 'index']
        );

        Route::get(
            'organisations/{organisation}',
            [AdminOrganisationController::class, 'show']
        );

        Route::post(
            'licenses',
            [AdminLicenseController::class, 'store']
        );

        Route::post(
            'licenses/{license}/revoke',
            [AdminLicenseController::class, 'revoke']
        );

        Route::post(
            'organisations/{organisation}/suspend',
            [AdminOrganisationStatusController::class, 'suspend']
        );

        Route::post(
            'organisations/{organisation}/reactivate',
            [AdminOrganisationStatusController::class, 'reactivate']
        );

        Route::delete(
            'organisations/{organisation}',
            [AdminOrganisationDeletionController::class, 'destroy']
        );

        Route::post(
            'users/{user}/resend-verification',
            [AdminSupportController::class, 'resendVerification']
        );

        Route::patch(
            'users/{user}/active',
            [AdminSupportController::class, 'setActive']
        );

        Route::post(
            'users/{user}/password-reset',
            [AdminSupportController::class, 'sendPasswordReset']
        );

        Route::post(
            'organisations/{organisation}/users',
            [AdminSupportController::class, 'createUser']
        );

        Route::patch(
            'users/{user}/role',
            [AdminSupportController::class, 'changeRole']
        );

        /*
         * V1.0.48: the one deliberate bypass of the three-step email
         * flow — a customer who cannot reach their old mailbox writes to
         * support, and platform staff set the new address from here.
         * Never organisation administrators.
         */
        Route::patch(
            'users/{user}/email',
            [AdminSupportController::class, 'changeEmail']
        );

        /*
        |------------------------------------------------------------------
        | Support mailbox
        |------------------------------------------------------------------
        |
        | Sent and received platform mail, read straight from Resend, plus
        | sending. Nothing is mirrored locally.
        |
        */

        Route::get(
            'emails',
            [AdminEmailController::class, 'index']
        );

        Route::get(
            'emails/mailboxes',
            [AdminEmailController::class, 'mailboxes']
        );

        Route::get(
            'emails/{id}',
            [AdminEmailController::class, 'show']
        );

        Route::post(
            'emails',
            [AdminEmailController::class, 'store']
        );

        /*
        |------------------------------------------------------------------
        | Customer records
        |------------------------------------------------------------------
        |
        | Read a customer organisation's operational data, and correct a
        | Lease on their behalf. Both run inside OrganisationContext so
        | the ordinary tenant scopes still apply.
        |
        */

        Route::get(
            'organisations/{organisation}/records',
            [AdminOrganisationDataController::class, 'index']
        );

        Route::get(
            'organisations/{organisation}/leases/{lease}',
            [AdminLeaseController::class, 'show']
        );

        Route::patch(
            'organisations/{organisation}/leases/{lease}',
            [AdminLeaseController::class, 'update']
        );
    });

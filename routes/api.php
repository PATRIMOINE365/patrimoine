<?php

use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\OwnerExpenseController;
use App\Http\Controllers\Api\OwnerLedgerController;
use App\Http\Controllers\Api\OwnerPayoutController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RentReserveController;
use App\Http\Controllers\Api\SecurityDepositController;
use App\Http\Controllers\Api\TenantFundController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConsumableAdvanceController;

/*
|--------------------------------------------------------------------------
| Patrimoine API Routes
|--------------------------------------------------------------------------
|
| Authentication middleware will be added once the initial API surface
| has been completed and tested.
|
*/

Route::apiResource('parties', PartyController::class);
Route::apiResource('buildings', BuildingController::class);
Route::apiResource('units', UnitController::class);
Route::apiResource('leases', LeaseController::class);

/*
 * Dashboard and operational reporting.
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
 * Payments are transactional records and intentionally do not expose
 * generic update/delete operations.
 */
Route::get('payments', [PaymentController::class, 'index']);
Route::post('payments', [PaymentController::class, 'store']);
Route::get('payments/{payment}', [PaymentController::class, 'show']);

/*
 * Classify unapplied tenant Payment money into dedicated held funds.
 */
Route::post(
    'payments/{payment}/tenant-funds',
    [TenantFundController::class, 'allocate']
);

/*
 * Consume protected Rent Reserve once termination notice permits it.
 */
Route::post(
    'tenant-funds/{tenantFundAccount}/consume-rent',
    [RentReserveController::class, 'consume']
);

/*
 * Consume tenant Consumable Advance against rent during the normal
 * Lease lifecycle.
 */
Route::post(
    'tenant-funds/{tenantFundAccount}/consume-advance',
    [ConsumableAdvanceController::class, 'consume']
);

/*
 * Finalize Security Deposit deductions, refund and tenant debt.
 */
Route::post(
    'leases/{lease}/security-deposit/settle',
    [SecurityDepositController::class, 'settle']
);

/*
 * Owner financial operations.
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

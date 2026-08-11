<?php

use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PaymentController;
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

Route::apiResource('parties', PartyController::class);
Route::apiResource('buildings', BuildingController::class);
Route::apiResource('units', UnitController::class);
Route::apiResource('leases', LeaseController::class);

/*
 * Payments are transactional records.
 *
 * Patrimoine does not expose generic update/delete operations because
 * financial history should not be casually edited after allocation.
 * Corrections will later use explicit reversal/adjustment workflows.
 */
Route::get('payments', [PaymentController::class, 'index']);
Route::post('payments', [PaymentController::class, 'store']);
Route::get('payments/{payment}', [PaymentController::class, 'show']);

<?php

use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\PartyController;
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

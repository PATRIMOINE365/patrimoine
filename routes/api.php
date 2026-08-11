<?php

use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patrimoine API Routes
|--------------------------------------------------------------------------
|
| Authentication middleware will be added once the initial application
| API surface has been completed and tested.
|
*/

Route::apiResource('parties', PartyController::class);

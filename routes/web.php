<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patrimoine Web Interface
|--------------------------------------------------------------------------
|
| These routes serve the browser UI only.
|
| Business data continues to be supplied by the authenticated API.
|
*/

Route::get(
    '/',
    function () {
        return redirect('/login');
    }
);

Route::view(
    '/login',
    'auth.login'
)->name('login');

Route::view(
    '/dashboard',
    'app.dashboard'
)->name('dashboard');

Route::view(
    '/properties',
    'app.properties'
)->name('properties');

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
    '/setup',
    'auth.setup'
)->name('setup');

Route::view(
    '/login',
    'auth.login'
)->name('login');

Route::view(
    '/forgot-password',
    'auth.forgot-password'
)->name('forgot-password');

Route::view(
    '/reset-password',
    'auth.reset-password'
)->name('reset-password');

Route::view(
    '/invitation',
    'auth.invitation'
)->name('invitation');

Route::view(
    '/dashboard',
    'app.dashboard'
)->name('dashboard');

Route::view(
    '/properties',
    'app.properties'
)->name('properties');

Route::view(
    '/parties',
    'app.parties'
)->name('parties');

Route::view(
    '/leases',
    'app.leases'
)->name('leases');

Route::view(
    '/owners',
    'app.owners'
)->name('owners');

Route::view(
    '/tenants',
    'app.tenants'
)->name('tenants');

Route::view(
    '/reports',
    'app.reports'
)->name('reports');

Route::view(
    '/activity-log',
    'app.activity-log'
)->name('activity-log');

Route::view(
    '/financial-journal',
    'app.financial-journal'
)->name('financial-journal');

Route::view(
    '/users',
    'app.users'
)->name('users');

Route::view(
    '/settings',
    'app.settings'
)->name('settings');

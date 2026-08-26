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

/*
 * V1.0.10: the one-time setup wizard is retired; multi-tenant signup
 * provisions new organisations. The old path forwards politely.
 */
Route::redirect('/setup', '/signup');

Route::view(
    '/signup',
    'auth.signup'
)->name('signup');

Route::view(
    '/verify-email',
    'auth.verify-email'
)->name('verify-email');

/*
 * Public legal pages. Linked from signup, the application footer and
 * outbound email.
 */
Route::view(
    '/terms',
    'legal.terms'
)->name('terms');

Route::view(
    '/privacy',
    'legal.privacy'
)->name('privacy');

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

/*
 * V1.0.7: in-app Help & Documentation and Update log,
 * available to every authenticated role.
 */
Route::view(
    '/help',
    'app.help'
)->name('help');

/*
 * V1.0.10: licence & plan page for the authenticated organisation.
 */
Route::view(
    '/license',
    'app.license'
)->name('license');

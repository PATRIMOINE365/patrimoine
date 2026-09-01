<?php


use App\Http\Controllers\AppAssociationController;
use App\Http\Controllers\ErrorReferenceController;
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

/*
|--------------------------------------------------------------------------
| Application Association (V1.0.44)
|--------------------------------------------------------------------------
|
| The two files that let an ordinary Patrimoine https link open the
| installed application instead of a browser tab.
|
| They are published before the first build exists, and the deep-link
| paths they claim are frozen from this release, because a link in an
| invoice e-mail is opened months after it is sent: whatever shape the
| link had when it was sent is the shape it will still have then.
|
| Both are withheld until the signing identities are configured. Apple
| caches the association through its own CDN, so a placeholder would be
| the answer that sticks.
|
*/
Route::get(
    '/.well-known/apple-app-site-association',
    [AppAssociationController::class, 'apple']
)->name('well-known.apple');

Route::get(
    '/.well-known/assetlinks.json',
    [AppAssociationController::class, 'android']
)->name('well-known.android');

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
/*
 * The Error codes reference runs without a session.
 *
 * Sessions live in the database, and this page exists for the moments
 * the database is unreachable: PM-9904 and PM-9905 are precisely what
 * somebody looks up during an outage. It reads nothing, writes nothing
 * and needs no visitor identity, so the session, the CSRF token and the
 * tenant context are all detached rather than left to fail.
 */
$errorPageExclusions = [
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,

    /*
     * The forgery guard reads the session, so it has to go with it. The
     * class was renamed between Laravel versions and both names are
     * listed: excluding a class that is not in the stack costs nothing,
     * while missing the one that is leaves the page dead.
     */
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,

    \App\Http\Middleware\SetOrganisationContext::class,
];

/*
 * The Error codes reference.
 *
 * Public, and deliberately so: somebody who cannot sign in is exactly
 * the person holding a code they need explained. /errors/PM-4045 opens
 * the page at that code, which is where every error message links to.
 */
Route::get(
    '/errors',
    ErrorReferenceController::class
)->name('errors')->withoutMiddleware($errorPageExclusions);

Route::get(
    '/errors/{code}',
    ErrorReferenceController::class
)
    ->name('errors.show')
    ->where('code', '[A-Za-z]{2}-[0-9]{4}')
    ->withoutMiddleware($errorPageExclusions);

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

/*
 * V1.0.29: the guided lease wizard is a page of its own rather than a
 * drawer — ten steps do not belong in a panel.
 */
Route::view(
    '/leases/wizard',
    'app.lease-wizard'
)->name('leases.wizard');

Route::view(
    '/owners',
    'app.owners'
)->name('owners');

Route::view(
    '/tenants',
    'app.tenants'
)->name('tenants');

Route::view(
    '/accounting',
    'app.accounting'
)->name('accounting');

Route::view(
    '/reports',
    'app.reports'
)->name('reports');

/*
 * V1.0.38: the activity log and the financial journal became the two tabs
 * of Audit. One is the record of ACTIONS and the other the record of MONEY,
 * and somebody checking either is doing the same job — they did not need a
 * sidebar entry each.
 *
 * Both old paths are kept, exactly as /users and /license were when they
 * moved into Settings: links printed on documents, sent in old e-mails and
 * sitting in anybody's bookmarks still land on the right tab.
 */
Route::view(
    '/audit',
    'app.audit'
)->name('audit');

Route::redirect(
    '/activity-log',
    '/audit#activity'
)->name('activity-log');

Route::redirect(
    '/financial-journal',
    '/audit#journal'
)->name('financial-journal');

/*
 * V1.0.32: Users and Licence now live inside Settings. Both paths are kept
 * so links, bookmarks and anything printed before the move still land in
 * the right place.
 */
Route::redirect(
    '/users',
    '/settings#users'
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
 * V1.0.10: licence & plan for the authenticated organisation. A tab of
 * Settings since V1.0.32.
 */
Route::redirect(
    '/license',
    '/settings#license'
)->name('license');

/*
 * V1.0.11: the platform administration console. The API behind it is
 * platform.admin-guarded; the shell page itself 403s for non-staff
 * through the browser module's own check.
 */
Route::view(
    '/admin',
    'app.admin'
)->name('admin');

/*
 * V1.0.42: the archive. Records Patrimoine will not delete, put out of
 * the way, and the one place they are still visible.
 */
Route::view(
    '/archive',
    'app.archive'
)->name('archive');

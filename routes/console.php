<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Patrimoine Console Routes
|--------------------------------------------------------------------------
|
| Console-only utility commands and scheduled application jobs live here.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automatic Rent Increments
|--------------------------------------------------------------------------
|
| Apply previously approved rent increments whose effective dates have
| arrived.
|
| This job runs before automated billing so an Invoice generated on an
| increment's effective date snapshots the newly effective contractual rent.
|
| The underlying service is idempotent and overdue scheduled increments are
| picked up by the next successful daily run.
|
*/
Schedule::command('patrimoine:apply-due-rent-increments')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Automated Billing
|--------------------------------------------------------------------------
|
| Generate every missing Invoice whose billing period has started by the
| current application date.
|
| Billing runs after due rent increments have been applied so newly effective
| rent is available before the day's Invoice generation begins.
|
| The underlying generation process is idempotent, so a repeated run will not
| create duplicate billing periods.
|
*/
Schedule::command('patrimoine:generate-due-invoices')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Rent Increment Notices
|--------------------------------------------------------------------------
|
| Notify tenants three calendar months before an approved rent increment
| becomes effective.
|
| Missed notices remain eligible until the day before the effective date.
|
*/
Schedule::command('patrimoine:send-rent-increment-notices')
    ->dailyAt('07:45')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Rent Reminders
|--------------------------------------------------------------------------
|
| Run daily and remind tenants whose Invoice is due today or overdue.
|
*/
Schedule::command('patrimoine:send-rent-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Plan Expiry Reminders (V1.0.11)
|--------------------------------------------------------------------------
|
| Customer administrators hear 7 days and 1 day before their trial or
| licence ends; billing@ receives a weekly forward look.
|
*/
Schedule::command('patrimoine:send-plan-expiry-reminders')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('patrimoine:send-platform-expiry-digest')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->onOneServer();

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
| Automated Billing
|--------------------------------------------------------------------------
|
| Run daily and generate every missing Invoice whose billing period has
| started by the current application date.
|
| The underlying generation process is idempotent, so a repeated run on
| the same day will not create duplicate billing periods.
|
*/
Schedule::command('patrimoine:generate-due-invoices')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

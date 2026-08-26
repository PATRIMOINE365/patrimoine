<?php

namespace App\Providers;

use App\Listeners\AttachPlainTextEmailPart;
use App\Support\OrganisationContext;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * V1.0.10 multi-tenancy: one organisation context per
         * application lifecycle. "scoped" (rather than "singleton")
         * guarantees a fresh, unbound context for every request and
         * every test application instance.
         */
        $this->app->scoped(OrganisationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * V1.0.17: every email ships a plain-text alternative alongside
         * its HTML body. HTML-only mail scores worse with spam and phish
         * filters — one of the signals behind the Microsoft 365
         * quarantine of our verification email.
         */
        Event::listen(
            MessageSending::class,
            AttachPlainTextEmailPart::class
        );
    }
}

<?php

namespace App\Providers;

use App\Listeners\AttachPlainTextEmailPart;
use App\Support\OrganisationContext;
use App\Support\PdfFonts;
use Dompdf\Dompdf;
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

        /*
         * V1.0.37: Inter for the PDF renderer, registered on every Dompdf
         * the container hands out. See App\Support\PdfFonts for why this
         * is not the @font-face rule it looks like it should be.
         *
         * In register() rather than boot(): a `resolving` callback only
         * fires for instances built AFTER it is added, and laravel-dompdf
         * can have made its renderer before any boot() method runs. Every
         * provider's register() runs before every provider's boot(), so
         * this is the earliest point that is guaranteed to be early enough.
         */
        $this->app->resolving(
            Dompdf::class,
            static fn (Dompdf $dompdf) => PdfFonts::register($dompdf)
        );
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

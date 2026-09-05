<?php

namespace App\Providers;

use App\Listeners\AttachPlainTextEmailPart;
use App\Models\PersonalAccessToken;
use App\Support\OrganisationContext;
use App\Support\PdfFonts;
use Dompdf\Dompdf;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

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
         * V1.0.51: one password rule for the whole product.
         *
         * Invitations, resets and changes accepted eight characters of
         * anything — a platform staff account was set up with
         * "aaaaaaaa" during the console audit — while signup asked for
         * ten with letters and numbers and the bootstrap command for
         * twelve. Every path now reads this one definition: twelve
         * characters, both cases, a digit, and in production a check
         * against the public breach corpus.
         */
        Password::defaults(function (): Password {
            $rule = Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });

        /*
         * V1.0.44: an access token is a device.
         *
         * Sanctum's own model is a bearer credential and nothing else.
         * Patrimoine's carries what the token was minted for and the
         * ceiling it may not outlive, both of which have to be decided
         * when the token is created because nothing about a bearer token
         * can be recovered afterwards.
         */
        Sanctum::usePersonalAccessTokenModel(
            PersonalAccessToken::class
        );

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

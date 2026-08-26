<?php

namespace App\Providers;

use App\Support\OrganisationContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * V1.1.0 multi-tenancy: one organisation context per
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
        //
    }
}

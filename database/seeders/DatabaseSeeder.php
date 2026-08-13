<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Root Patrimoine database seeder.
 *
 * Patrimoine does not create default users or business records automatically.
 *
 * This is intentional for production safety:
 *
 * - no predictable administrator credentials are shipped with the application;
 * - no demonstration Parties, Buildings, Leases or financial transactions are
 *   created in a production database;
 * - development/UI scenarios remain available only through explicitly named
 *   seeders that an operator must invoke manually.
 *
 * After a fresh production installation, create the first Property Manager
 * securely with:
 *
 *     php artisan patrimoine:create-admin
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The default production seed operation intentionally performs no writes.
     */
    public function run(): void
    {
        /*
         * Intentionally empty.
         *
         * Development/test seeders must always be invoked explicitly by class
         * name and must never be included here.
         */
    }
}

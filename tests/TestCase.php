<?php

namespace Tests;

use App\Models\License;
use App\Models\Organisation;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The organisation the current test runs inside, when the test
     * refreshes the database.
     */
    protected ?Organisation $testOrganisation = null;

    /**
     * V1.0.10 multi-tenancy: the entire pre-existing suite exercises ONE
     * organisation, exactly as production did before multi-tenancy.
     *
     * Database-backed tests therefore start with a default organisation
     * created and bound as the current context, so factories, seeders
     * and direct model creation keep working unchanged. HTTP calls made
     * by tests re-bind the context from the acting user (who belongs to
     * the same first organisation through UserFactory).
     *
     * Isolation tests create additional organisations explicitly and
     * re-bind via OrganisationContext::runAs().
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (
            in_array(
                RefreshDatabase::class,
                class_uses_recursive(static::class),
                true
            )
        ) {
            $this->testOrganisation = Organisation::query()->create([
                'name' => 'Test Organisation',
                'status' => 'active',
                'trial_ends_on' => null,
            ]);

            /*
             * A perpetual Professional licence, exactly like the
             * grandfathered founding installation: the pre-existing
             * suite exercises every feature without plan friction.
             * Licensing tests drop this licence to observe Free/trial
             * behaviour.
             */
            License::query()->create([
                'organisation_id' => $this->testOrganisation->id,
                'plan' => 'professional',
                'starts_on' => now()->toDateString(),
                'expires_on' => null,
                'notes' => 'Test suite default licence.',
            ]);

            OrganisationContext::bind(
                (int) $this->testOrganisation->id
            );
        }
    }
}

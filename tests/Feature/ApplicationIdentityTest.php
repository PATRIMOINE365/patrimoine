<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\Party;
use App\Services\ApplicationIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies resolution of Patrimoine's single-tenant business identity.
 */
class ApplicationIdentityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Before initial configuration, Patrimoine remains the safe
     * presentation fallback.
     */
    public function test_identity_falls_back_to_patrimoine_before_configuration(): void
    {
        $identity = app(
            ApplicationIdentityService::class
        );

        $this->assertNull(
            $identity->managingOrganisation()
        );

        $this->assertSame(
            'Patrimoine',
            $identity->displayName()
        );

        $this->assertNull(
            $identity->email()
        );
    }

    /**
     * The configured managing organisation becomes the application's
     * business identity.
     */
    public function test_identity_resolves_configured_managing_organisation(): void
    {
        $organisation = Party::create([
            'type' => 'organisation',
            'legal_name' => 'Acme Property Management Limited',
            'address' => 'Accra, Ghana',
            'email' => 'accounts@acme.example',
            'contact_person_name' => 'Property Manager',
            'contact_person_phone' => '0200000000',
            'contact_person_email' => 'manager@acme.example',
        ]);

        $organisation->roles()->create([
            'role' => 'managing_organisation',
        ]);

        ApplicationSetting::create([
            'managing_organisation_party_id' =>
                $organisation->id,
        ]);

        $identity = app(
            ApplicationIdentityService::class
        );

        $resolved = $identity
            ->managingOrganisation();

        $this->assertNotNull($resolved);

        $this->assertTrue(
            $resolved->is($organisation)
        );

        $this->assertSame(
            'Acme Property Management Limited',
            $identity->displayName()
        );

        $this->assertSame(
            'accounts@acme.example',
            $identity->email()
        );
    }
}

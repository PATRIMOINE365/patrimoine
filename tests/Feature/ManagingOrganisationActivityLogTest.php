<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManagingOrganisationActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_configuration_records_one_create_event(): void
    {
        Sanctum::actingAs($this->administrator());

        $this
            ->putJson(
                '/api/managing-organisation',
                $this->payload()
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'managing_organisation.created',
            $event->action
        );

        $this->assertSame(
            'managing_organisation',
            $event->entity_type
        );

        $this->assertSame(
            'Activity Management Ltd',
            $event->snapshot['legal_name']
        );

        $this->assertSame(
            'GHS',
            $event->snapshot['currency']
        );

        $this->assertSame(
            'en',
            $event->snapshot['language']
        );

        $this->assertSame(
            ['managing_organisation'],
            $event->snapshot['roles']
        );
    }

    public function test_update_records_only_changed_configuration_fields(): void
    {
        Sanctum::actingAs($this->administrator());

        $this
            ->putJson(
                '/api/managing-organisation',
                $this->payload()
            )
            ->assertOk();

        ActivityLog::query()->delete();

        $this
            ->putJson(
                '/api/managing-organisation',
                array_merge(
                    $this->payload(),
                    [
                        'currency' => 'FCFA',
                        'default_vat_rate' => 17.5,
                    ]
                )
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'managing_organisation.updated',
            $event->action
        );

        $this->assertSame(
            [
                'default_vat_rate' => '18.00',
                'currency' => 'GHS',
            ],
            $event->before_values
        );

        $this->assertSame(
            [
                'default_vat_rate' => '17.50',
                'currency' => 'FCFA',
            ],
            $event->after_values
        );
    }

    public function test_same_configuration_is_not_logged_as_update(): void
    {
        Sanctum::actingAs($this->administrator());

        $this
            ->putJson(
                '/api/managing-organisation',
                $this->payload()
            )
            ->assertOk();

        ActivityLog::query()->delete();

        $this
            ->putJson(
                '/api/managing-organisation',
                $this->payload()
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_managing_organisation_action_is_not_duplicated_as_party_event(): void
    {
        Sanctum::actingAs($this->administrator());

        $this
            ->putJson(
                '/api/managing-organisation',
                $this->payload()
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            1
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' => 'party.created',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'legal_name' => 'Activity Management Ltd',
            'address' => 'Accra',
            'contact_person_name' => 'Activity Manager',
            'contact_person_phone' => '0300000001',
            'contact_person_email' => 'manager@example.test',
            'phone' => '0300000002',
            'email' => 'management@example.test',
            'default_vat_rate' => 18,
            'language' => 'en',
            'currency' => 'GHS',
        ];
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Freeze Activity P API behavior.
 *
 * Activity Log access is Administrator-only, immutable and passive.
 */
class ActivityLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_requires_authentication(): void
    {
        $this
            ->getJson('/api/activity-log')
            ->assertUnauthorized();
    }

    public function test_only_administrator_can_access_activity_log(): void
    {
        $event = $this->event([
            'action' => 'test.authorization',
        ]);

        foreach (
            [
                UserRole::PropertyManager,
                UserRole::Viewer,
            ] as $role
        ) {
            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,
                ])
            );

            $this
                ->getJson('/api/activity-log')
                ->assertForbidden();

            $this
                ->getJson(
                    "/api/activity-log/{$event->id}"
                )
                ->assertForbidden();
        }
    }

    public function test_administrator_receives_paginated_newest_first_events(): void
    {
        $this->event([
            'action' => 'test.oldest',
            'created_at' => '2026-08-10 09:00:00',
        ]);

        $this->event([
            'action' => 'test.middle',
            'created_at' => '2026-08-11 09:00:00',
        ]);

        $this->event([
            'action' => 'test.newest',
            'created_at' => '2026-08-12 09:00:00',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $response = $this
            ->getJson(
                '/api/activity-log?per_page=2'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.0.action',
                'test.newest'
            )
            ->assertJsonPath(
                'data.1.action',
                'test.middle'
            )
            ->assertJsonPath(
                'total',
                3
            )
            ->assertJsonPath(
                'per_page',
                2
            );

        /*
         * List responses are deliberately lightweight. Structured historical
         * detail is retrieved through the show endpoint.
         */
        $this->assertArrayNotHasKey(
            'snapshot',
            $response->json('data.0')
        );
    }

    public function test_filters_can_be_combined(): void
    {
        $target = User::factory()->create([
            'name' => 'Target Manager',
            'role' => UserRole::PropertyManager,
        ]);

        $other = User::factory()->create([
            'name' => 'Other Viewer',
            'role' => UserRole::Viewer,
        ]);

        $matching = $this->event([
            'actor' => $target,
            'action' => 'payment.recorded',
            'entity_type' => 'payment',
            'entity_id' => '41',
            'entity_label' => 'Payment #41',
            'created_at' => '2026-08-10 12:00:00',
        ]);

        $this->event([
            'actor' => $target,
            'action' => 'building.updated',
            'entity_type' => 'building',
            'created_at' => '2026-08-10 13:00:00',
        ]);

        $this->event([
            'actor' => $other,
            'action' => 'payment.recorded',
            'entity_type' => 'payment',
            'created_at' => '2026-08-10 14:00:00',
        ]);

        $this->event([
            'actor' => $target,
            'action' => 'payment.recorded',
            'entity_type' => 'payment',
            'created_at' => '2026-08-12 12:00:00',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/activity-log'
                .'?from=2026-08-10'
                .'&to=2026-08-10'
                ."&user_id={$target->id}"
                .'&role=property_manager'
                .'&action=payment.recorded'
                .'&entity_type=payment'
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );
    }

    public function test_free_text_search_uses_frozen_and_structured_context(): void
    {
        $actor = User::factory()->create([
            'name' => 'Needle Operator',
            'email' => 'needle.operator@example.test',
            'role' => UserRole::PropertyManager,
        ]);

        $matching = $this->event([
            'actor' => $actor,
            'action' => 'payment.recorded',
            'entity_type' => 'payment',
            'entity_label' => 'Tenant Payment Alpha',
            'metadata' => [
                'reference' => 'Needle-Reference-7744',
            ],
            'snapshot' => [
                'amount' => 5000,
                'notes' => 'Historical Search Context',
            ],
        ]);

        $this->event([
            'actor_name' => 'Completely Different Actor',
            'action' => 'building.created',
            'entity_type' => 'building',
            'entity_label' => 'Unrelated Building',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/activity-log?search=Needle%20Operator'
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );

        /*
         * Metadata/snapshot context must also be searchable even though those
         * large structured fields are excluded from the list projection.
         */
        $this
            ->getJson(
                '/api/activity-log?search=Needle-Reference-7744'
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );

        $this
            ->getJson(
                '/api/activity-log?search=Historical%20Search%20Context'
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $matching->id
            );
    }

    public function test_detail_returns_complete_historical_event(): void
    {
        $event = $this->event([
            'action' => 'party.updated',
            'actor_name' => 'Historical Administrator',
            'actor_email' => 'historical@example.test',
            'actor_role' => 'administrator',
            'entity_type' => 'party',
            'entity_id' => '55',
            'entity_label' => 'Historical Party',
            'before' => [
                'name' => 'Before Name',
            ],
            'after' => [
                'name' => 'After Name',
            ],
            'snapshot' => [
                'type' => 'person',
            ],
            'metadata' => [
                'source' => 'test',
            ],
            'ip_address' => '192.0.2.25',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                "/api/activity-log/{$event->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $event->id
            )
            ->assertJsonPath(
                'actor_name',
                'Historical Administrator'
            )
            ->assertJsonPath(
                'action',
                'party.updated'
            )
            ->assertJsonPath(
                'entity_type',
                'party'
            )
            ->assertJsonPath(
                'entity_id',
                '55'
            )
            ->assertJsonPath(
                'before_values.name',
                'Before Name'
            )
            ->assertJsonPath(
                'after_values.name',
                'After Name'
            )
            ->assertJsonPath(
                'snapshot.type',
                'person'
            )
            ->assertJsonPath(
                'metadata.source',
                'test'
            )
            ->assertJsonPath(
                'ip_address',
                '192.0.2.25'
            );
    }

    public function test_activity_log_reads_do_not_create_activity_events(): void
    {
        $event = $this->event([
            'action' => 'test.passive_read',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson('/api/activity-log')
            ->assertOk();

        $this
            ->getJson(
                "/api/activity-log/{$event->id}"
            )
            ->assertOk();

        /*
         * Activity Log browsing/searching is passive and must not recursively
         * create Activity Log events.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_activity_log_filter_validation_is_strict(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson(
                '/api/activity-log?from=not-a-date'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'from',
            ]);

        $this
            ->getJson(
                '/api/activity-log'
                .'?from=2026-08-20'
                .'&to=2026-08-19'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to',
            ]);

        $this
            ->getJson(
                '/api/activity-log?role=not-a-role'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);

        $this
            ->getJson(
                '/api/activity-log?per_page=101'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
            ]);
    }

    public function test_activity_log_has_no_write_or_delete_api(): void
    {
        $event = $this->event([
            'action' => 'test.immutable_api',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->postJson(
                '/api/activity-log',
                []
            )
            ->assertMethodNotAllowed();

        $this
            ->patchJson(
                "/api/activity-log/{$event->id}",
                [
                    'action' => 'tampered',
                ]
            )
            ->assertMethodNotAllowed();

        $this
            ->deleteJson(
                "/api/activity-log/{$event->id}"
            )
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'id' => $event->id,
                'action' => 'test.immutable_api',
            ]
        );
    }

    /**
     * Create one historical event through the real central recorder.
     *
     * @param  array<string, mixed>  $values
     */
    private function event(
        array $values = []
    ): ActivityLog {
        $createdAt = Carbon::parse(
            $values['created_at']
            ?? '2026-08-16 12:00:00'
        );

        Carbon::setTestNow(
            $createdAt
        );

        try {
            return app(
                ActivityLogService::class
            )->record(
                action: $values['action']
                    ?? 'test.event',

                actor: $values['actor']
                    ?? null,

                entityType: $values['entity_type']
                    ?? null,

                entityId: $values['entity_id']
                    ?? null,

                entityLabel: $values['entity_label']
                    ?? null,

                before: $values['before']
                    ?? null,

                after: $values['after']
                    ?? null,

                snapshot: $values['snapshot']
                    ?? null,

                metadata: $values['metadata']
                    ?? null,

                actorName: $values['actor_name']
                    ?? null,

                actorEmail: $values['actor_email']
                    ?? null,

                actorRole: $values['actor_role']
                    ?? null,

                ipAddress: $values['ip_address']
                    ?? '192.0.2.10',
            );
        } finally {
            Carbon::setTestNow();
        }
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

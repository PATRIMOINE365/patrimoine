<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityLogFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_records_frozen_actor_and_entity_context(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Administrator',
            'email' => 'original@example.test',
            'role' => UserRole::Administrator,
        ]);

        $request = Request::create(
            '/api/example',
            'POST',
            server: [
                'REMOTE_ADDR' => '192.0.2.25',
            ]
        );

        $request->setUserResolver(
            fn (): User => $user
        );

        $event = app(ActivityLogService::class)->record(
            action: 'test.recorded',
            request: $request,
            entityType: 'test_entity',
            entityId: 42,
            entityLabel: 'Example Record',
            before: [
                'name' => 'Before',
            ],
            after: [
                'name' => 'After',
            ],
            metadata: [
                'reason' => 'foundation-test',
            ],
        );

        $this->assertSame(
            $user->id,
            $event->user_id
        );
        $this->assertSame(
            'Original Administrator',
            $event->actor_name
        );
        $this->assertSame(
            'original@example.test',
            $event->actor_email
        );
        $this->assertSame(
            UserRole::Administrator->value,
            $event->actor_role
        );
        $this->assertSame(
            'test.recorded',
            $event->action
        );
        $this->assertSame(
            'test_entity',
            $event->entity_type
        );
        $this->assertSame(
            '42',
            $event->entity_id
        );
        $this->assertSame(
            'Example Record',
            $event->entity_label
        );
        $this->assertSame(
            ['name' => 'Before'],
            $event->before_values
        );
        $this->assertSame(
            ['name' => 'After'],
            $event->after_values
        );
        $this->assertSame(
            ['reason' => 'foundation-test'],
            $event->metadata
        );
        $this->assertSame(
            '192.0.2.25',
            $event->ip_address
        );
    }

    public function test_identity_snapshot_survives_user_changes_and_deletion(): void
    {
        $user = User::factory()->create([
            'name' => 'Historical User',
            'email' => 'historical@example.test',
            'role' => UserRole::PropertyManager,
        ]);

        $event = app(ActivityLogService::class)->record(
            action: 'test.identity_snapshot',
            actor: $user,
        );

        $user->update([
            'name' => 'Changed Name',
            'email' => 'changed@example.test',
            'role' => UserRole::Viewer,
        ]);

        $event->refresh();

        $this->assertSame(
            'Historical User',
            $event->actor_name
        );
        $this->assertSame(
            'historical@example.test',
            $event->actor_email
        );
        $this->assertSame(
            UserRole::PropertyManager->value,
            $event->actor_role
        );

        $user->delete();

        $event->refresh();

        $this->assertNull($event->user_id);
        $this->assertSame(
            'Historical User',
            $event->actor_name
        );
        $this->assertSame(
            'historical@example.test',
            $event->actor_email
        );
        $this->assertSame(
            UserRole::PropertyManager->value,
            $event->actor_role
        );
    }

    public function test_service_can_record_event_without_resolved_user(): void
    {
        $event = app(ActivityLogService::class)->record(
            action: 'test.unresolved_actor',
            actorEmail: 'unknown@example.test',
            ipAddress: '198.51.100.10',
            metadata: [
                'outcome' => 'failed',
            ],
        );

        $this->assertNull($event->user_id);
        $this->assertNull($event->actor_name);
        $this->assertSame(
            'unknown@example.test',
            $event->actor_email
        );
        $this->assertNull($event->actor_role);
        $this->assertSame(
            '198.51.100.10',
            $event->ip_address
        );
        $this->assertSame(
            ['outcome' => 'failed'],
            $event->metadata
        );
    }

    public function test_activity_log_model_rejects_normal_update_and_delete(): void
    {
        $event = app(ActivityLogService::class)->record(
            action: 'test.immutable',
        );

        $originalId = $event->id;

        $updated = $event->update([
            'action' => 'test.changed',
        ]);

        $this->assertFalse($updated);

        $event->refresh();

        $this->assertSame(
            'test.immutable',
            $event->action
        );

        $deleted = $event->delete();

        $this->assertFalse($deleted);

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'id' => $originalId,
                'action' => 'test.immutable',
            ]
        );
    }

    public function test_structured_values_preserve_zero_and_false(): void
    {
        $event = app(ActivityLogService::class)->record(
            action: 'test.values',
            snapshot: [
                'amount' => 0,
                'active' => false,
                'unused' => null,
            ],
        );

        $this->assertSame(
            [
                'amount' => 0,
                'active' => false,
            ],
            $event->snapshot
        );
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Freeze the shared Activity Log export presentation layer.
 *
 * PDF and CSV exports must consume the same localized row projection rather
 * than independently interpreting immutable historical events.
 */
class ActivityLogExportPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_projection_uses_frozen_actor_identity(): void
    {
        $user =
            User::factory()->create([
                'name' => 'Current User Name',
                'email' => 'current@example.com',
                'role' => UserRole::Administrator,
            ]);

        $event =
            ActivityLog::create([
                'user_id' => $user->id,
                'actor_name' => 'Frozen Historical Name',
                'actor_email' => 'historical@example.com',
                'actor_role' => 'administrator',
                'action' => 'payment.recorded',
                'entity_type' => 'payment',
                'entity_id' => '42',
                'entity_label' => 'Payment #42',
                'before_values' => [
                    'amount' => 1000,
                ],
                'after_values' => [
                    'amount' => 1500,
                ],
                'snapshot' => [
                    'reference' => 'PAY-42',
                ],
                'metadata' => [
                    'source' => 'browser',
                ],
                'ip_address' => '192.0.2.10',
            ]);

        $user->update([
            'name' => 'Changed Later',
            'email' => 'changed@example.com',
        ]);

        app()->setLocale('en');

        $row =
            app(ActivityLogExportService::class)
                ->row(
                    $event->fresh()
                );

        $this->assertSame(
            'Frozen Historical Name',
            $row['actor_name']
        );

        $this->assertSame(
            'historical@example.com',
            $row['actor_email']
        );

        $this->assertSame(
            'Administrator',
            $row['actor_role']
        );

        $this->assertSame(
            'Payment Recorded',
            $row['action']
        );

        $this->assertSame(
            'Payment',
            $row['entity_type']
        );

        $this->assertSame(
            '42',
            $row['entity_id']
        );

        $this->assertSame(
            '192.0.2.10',
            $row['ip_address']
        );

        $this->assertStringContainsString(
            '"amount": 1000',
            $row['before_values']
        );

        $this->assertStringContainsString(
            '"amount": 1500',
            $row['after_values']
        );

        $this->assertStringContainsString(
            '"reference": "PAY-42"',
            $row['snapshot']
        );

        $this->assertStringContainsString(
            '"source": "browser"',
            $row['metadata']
        );
    }

    public function test_known_action_role_and_entity_are_localized_in_french(): void
    {
        app()->setLocale('fr');

        $service =
            app(
                ActivityLogExportService::class
            );

        $this->assertSame(
            'Paiement enregistré',
            $service->action(
                'payment.recorded'
            )
        );

        $this->assertSame(
            'Administrateur',
            $service->role(
                'administrator'
            )
        );

        $this->assertSame(
            'Paiement',
            $service->entityType(
                'payment'
            )
        );

        $columns =
            $service->columns();

        $this->assertSame(
            'Horodatage',
            $columns['timestamp']
        );

        $this->assertSame(
            'Adresse IP',
            $columns['ip_address']
        );
    }

    public function test_unknown_identifiers_have_readable_fallbacks(): void
    {
        app()->setLocale('en');

        $service =
            app(
                ActivityLogExportService::class
            );

        $this->assertSame(
            'Future Action Added',
            $service->action(
                'future.action_added'
            )
        );

        $this->assertSame(
            'Future Record Type',
            $service->entityType(
                'future_record_type'
            )
        );

        $this->assertSame(
            'Future Role',
            $service->role(
                'future_role'
            )
        );
    }

    public function test_structured_export_preserves_zero_false_and_unicode(): void
    {
        app()->setLocale('en');

        $value =
            app(ActivityLogExportService::class)
                ->structured([
                    'amount' => 0,
                    'active' => false,
                    'name' => 'Élodie',
                ]);

        $this->assertStringContainsString(
            '"amount": 0',
            $value
        );

        $this->assertStringContainsString(
            '"active": false',
            $value
        );

        $this->assertStringContainsString(
            '"name": "Élodie"',
            $value
        );
    }

    public function test_null_and_empty_structures_export_as_empty_values(): void
    {
        $service =
            app(
                ActivityLogExportService::class
            );

        $this->assertSame(
            '',
            $service->structured(null)
        );

        $this->assertSame(
            '',
            $service->structured([])
        );
    }

    public function test_columns_have_stable_order(): void
    {
        app()->setLocale('en');

        $columns =
            app(ActivityLogExportService::class)
                ->columns();

        $this->assertSame(
            [
                'id',
                'timestamp',
                'actor_name',
                'actor_email',
                'actor_role',
                'action',
                'entity_type',
                'entity_id',
                'entity_label',
                'ip_address',
                'browser',
                'platform',
                'device',
                'before_values',
                'after_values',
                'snapshot',
                'metadata',
            ],
            array_keys(
                $columns
            )
        );
    }
}

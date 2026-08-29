<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Freeze Activity R downloadable Activity Log export behavior.
 *
 * Exporting is a meaningful human action and records one Activity Log event.
 * The event is recorded only after the result set has been resolved so an
 * export never contains its own export event.
 */
class ActivityLogExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_exports_require_authentication(): void
    {
        $this
            ->get('/api/activity-log/csv')
            ->assertUnauthorized();

        $this
            ->get('/api/activity-log/xlsx')
            ->assertUnauthorized();
    }

    public function test_only_administrator_can_export_activity_log(): void
    {
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
                ->get('/api/activity-log/csv')
                ->assertForbidden();

            $this
                ->get('/api/activity-log/xlsx')
                ->assertForbidden();
        }
    }

    public function test_csv_export_uses_current_filters_and_newest_first_order(): void
    {
        $target =
            User::factory()->create([
                'name' => 'Target Manager',
                'email' => 'target@example.test',
                'role' => UserRole::PropertyManager,
            ]);

        $this->event([
            'actor' => $target,
            'action' => 'payment.recorded',
            'entity_type' => 'payment',
            'entity_id' => '100',
            'entity_label' => 'Payment #100',
            'created_at' => '2026-08-10 10:00:00',
        ]);

        $this->event([
            'actor' => $target,
            'action' => 'payment.recorded',
            'entity_type' => 'payment',
            'entity_id' => '200',
            'entity_label' => 'Payment #200',
            'created_at' => '2026-08-10 12:00:00',
        ]);

        $this->event([
            'actor' => $target,
            'action' => 'building.updated',
            'entity_type' => 'building',
            'entity_id' => '300',
            'entity_label' => 'Building #300',
            'created_at' => '2026-08-10 13:00:00',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $response =
            $this->get(
                '/api/activity-log/csv'
                .'?from=2026-08-10'
                .'&to=2026-08-10'
                ."&user_id={$target->id}"
                .'&role=property_manager'
                .'&action=payment.recorded'
                .'&entity_type=payment'
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            );

        $content =
            (string) $response
                ->getContent();

        $this->assertStringContainsString(
            'Payment Recorded',
            $content
        );

        $this->assertStringContainsString(
            'Payment #200',
            $content
        );

        $this->assertStringContainsString(
            'Payment #100',
            $content
        );

        $this->assertStringNotContainsString(
            'Building #300',
            $content
        );

        /*
         * Newest matching event must appear first.
         */
        $this->assertLessThan(
            strpos(
                $content,
                'Payment #100'
            ),
            strpos(
                $content,
                'Payment #200'
            )
        );

        /*
         * Three source events plus one successful export event.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            4
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'activity_log.exported',

                'entity_type' => 'activity_log',

                'entity_label' => 'Activity Log',
            ]
        );

        $exportEvent =
            ActivityLog::query()
                ->where(
                    'action',
                    'activity_log.exported'
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            'csv',
            $exportEvent
                ->metadata['format']
        );

        $this->assertSame(
            2,
            $exportEvent
                ->metadata['record_count']
        );

        $this->assertSame(
            'payment.recorded',
            $exportEvent
                ->metadata[
                    'filters'
                ][
                    'action'
                ]
        );
    }

    public function test_export_does_not_contain_its_own_export_event(): void
    {
        $this->event([
            'actor_name' => 'Historical Administrator',

            'actor_email' => 'historical@example.test',

            'actor_role' => 'administrator',

            'action' => 'party.updated',

            'entity_type' => 'party',

            'entity_id' => '10',

            'entity_label' => 'Historical Party',

            'created_at' => '2026-08-10 12:00:00',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $response =
            $this
                ->get(
                    '/api/activity-log/csv'
                )
                ->assertOk();

        $content =
            (string) $response
                ->getContent();

        /*
         * The source event is exported.
         */
        $this->assertStringContainsString(
            'Historical Party',
            $content
        );

        /*
         * The export event is written only after the source rows have been
         * resolved, so it cannot recursively appear inside this same file.
         */
        $this->assertStringNotContainsString(
            'Activity Log Exported',
            $content
        );

        $this->assertDatabaseCount(
            'activity_logs',
            2
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'activity_log.exported',
            ]
        );
    }

    public function test_csv_export_is_localized_in_french(): void
    {
        /*
         * HTTP requests use Patrimoine's organisation-wide language setting.
         * ApplyApplicationLocale runs before the controller and therefore
         * persisted ApplicationSetting is the production source of truth.
         */
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $this->event([
            'actor_name' => 'Administrateur historique',

            'actor_email' => 'historique@example.test',

            'actor_role' => 'administrator',

            'action' => 'payment.recorded',

            'entity_type' => 'payment',

            'entity_id' => '55',

            'entity_label' => 'Paiement #55',

            'created_at' => '2026-08-10 12:00:00',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $response =
            $this
                ->get(
                    '/api/activity-log/csv'
                )
                ->assertOk();

        $content =
            (string) $response
                ->getContent();

        $this->assertStringContainsString(
            'Horodatage',
            $content
        );

        $this->assertStringContainsString(
            'Adresse IP',
            $content
        );

        $this->assertStringContainsString(
            'Paiement enregistré',
            $content
        );

        $this->assertStringContainsString(
            'Administrateur',
            $content
        );

        $this->assertDatabaseCount(
            'activity_logs',
            2
        );
    }

    /**
     * The Activity Log has no PDF export, and must not answer as if it had.
     *
     * dompdf builds a Frame per cell and holds the whole document in
     * memory, and this route rendered EVERY matching row. Because the log
     * is retained indefinitely the cost only rose: measured on the live box
     * at 32 rows using 102 MB of the 128 MB available, and pre-production
     * failed outright at 214. Withdrawn in V1.0.35 rather than capped,
     * because CSV and XLSX carry the same columns and stream.
     */
    public function test_the_activity_log_offers_no_pdf_export(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->getJson('/api/activity-log/pdf')
            ->assertNotFound();
    }

    public function test_export_filter_validation_matches_activity_log_api(): void
    {
        Sanctum::actingAs(
            $this->administrator()
        );

        foreach (
            [
                '/api/activity-log/csv',
                '/api/activity-log/xlsx',
            ] as $endpoint
        ) {
            $this
                ->getJson(
                    $endpoint
                    .'?from=not-a-date'
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'from',
                ]);

            $this
                ->getJson(
                    $endpoint
                    .'?role=not-a-role'
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'role',
                ]);

            $this
                ->getJson(
                    $endpoint
                    .'?from=2026-08-20'
                    .'&to=2026-08-19'
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'to',
                ]);
        }

        /*
         * Failed validation must not record an export event.
         */
        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' => 'activity_log.exported',
            ]
        );
    }

    public function test_each_successful_export_records_exactly_one_meaningful_event(): void
    {
        $this->event([
            'action' => 'test.export_source',
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->get(
                '/api/activity-log/csv'
            )
            ->assertOk();

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'action',
                    'activity_log.exported'
                )
                ->count()
        );

        $this
            ->get(
                '/api/activity-log/xlsx'
            )
            ->assertOk();

        $this->assertSame(
            2,
            ActivityLog::query()
                ->where(
                    'action',
                    'activity_log.exported'
                )
                ->count()
        );

        /*
         * One original source event plus exactly one event per export.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            3
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
        $createdAt =
            Carbon::parse(
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

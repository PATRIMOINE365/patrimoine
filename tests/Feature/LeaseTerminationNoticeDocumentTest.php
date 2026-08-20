<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Services\Documents\TerminationNoticeDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class LeaseTerminationNoticeDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function lease(
        string $status = 'notice'
    ): Lease {
        $building = Building::create([
            'name' => 'Termination Notice Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit TN-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Termination Notice Tenant',
            'phone' => '0200001900',
            'email' => 'termination-notice@example.test',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 10000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'status' => $status,
            'termination_notice_date' => $status === 'notice'
                    ? '2026-08-20'
                    : null,
            'termination_date' => $status === 'notice'
                    ? '2026-09-30'
                    : null,
            'termination_final_rent_mode' => $status === 'notice'
                    ? 'prorate'
                    : null,
            'termination_previous_status' => $status === 'notice'
                    ? 'active'
                    : null,
        ]);
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Administrator,
        ]);
    }

    public function test_service_generates_genuine_pdf(): void
    {
        $lease = $this->lease();

        $contents = app(
            TerminationNoticeDocumentService::class
        )->generate($lease);

        $this->assertStringStartsWith(
            '%PDF-',
            $contents
        );

        $this->assertGreaterThan(
            1000,
            strlen($contents)
        );
    }

    public function test_notice_can_be_downloaded_through_api(): void
    {
        $lease = $this->lease();

        Sanctum::actingAs(
            $this->administrator()
        );

        $response = $this->get(
            "/api/leases/{$lease->id}/termination-notice/pdf"
        );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $this->assertStringContainsString(
            "Patrimoine-Termination-Notice-Lease-{$lease->id}.pdf",
            (string) $response->headers->get(
                'Content-Disposition'
            )
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );
    }

    public function test_download_records_one_activity_log_event(): void
    {
        $lease = $this->lease();

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->get(
                "/api/leases/{$lease->id}/termination-notice/pdf"
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'lease.termination_notice_downloaded',
            $event->action
        );

        $this->assertSame(
            'lease',
            $event->entity_type
        );

        $this->assertSame(
            (string) $lease->id,
            $event->entity_id
        );

        $this->assertSame(
            'termination_notice',
            $event->metadata['document_type']
        );

        $this->assertSame(
            'pdf',
            $event->metadata['format']
        );

        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_active_lease_cannot_generate_notice(): void
    {
        $lease = $this->lease('active');

        $this->expectException(
            RuntimeException::class
        );

        app(
            TerminationNoticeDocumentService::class
        )->generate($lease);
    }

    public function test_active_lease_api_request_is_rejected_and_not_logged(): void
    {
        $lease = $this->lease('active');

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->get(
                "/api/leases/{$lease->id}/termination-notice/pdf"
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_english_and_french_translation_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);

            $this->assertNotSame(
                'documents.termination_notice.title',
                __(
                    'documents.termination_notice.title'
                )
            );

            $this->assertNotSame(
                'documents.termination_notice.body',
                __(
                    'documents.termination_notice.body',
                    [
                        'notice_date' => '20-08-2026',
                        'termination_date' => '30-09-2026',
                    ]
                )
            );
        }
    }
}

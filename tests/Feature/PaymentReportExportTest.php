<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_report_exports_generate_pdf_csv_and_xlsx(): void
    {
        $context =
            $this->fixture();

        $this->authenticate();

        $pdf =
            $this->get(
                '/api/reports/payments/pdf'
            );

        $pdf
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $this->assertStringStartsWith(
            '%PDF',
            $pdf->getContent()
        );

        $csv =
            $this->get(
                '/api/reports/payments/csv'
            );

        $csv
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            );

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $csv->getContent()
        );

        $this->assertStringContainsString(
            'PAY-'.$context['payment']->id,
            $csv->getContent()
        );

        $this->assertStringContainsString(
            'PAYMENT-EXPORT-001',
            $csv->getContent()
        );

        $xlsx =
            $this->get(
                '/api/reports/payments/xlsx'
            );

        $xlsx
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $this->assertStringStartsWith(
            'PK',
            $xlsx->getContent()
        );
    }

    public function test_payment_report_exports_use_the_same_filters_as_json_report(): void
    {
        $context =
            $this->fixture();

        Payment::create([
            'lease_id' => $context['lease']->id,

            'amount' => 9000,

            'payment_date' => '2026-07-15',

            'payment_method' => 'bank_transfer',

            'reference' => 'EXCLUDED-PAYMENT',
        ]);

        $this->authenticate();

        $query =
            '?from=2026-08-01'
            .'&to=2026-08-31'
            .'&tenant_id='.$context['tenant']->id
            .'&lease_id='.$context['lease']->id
            .'&building_id='.$context['building']->id
            .'&unit_id='.$context['unit']->id
            .'&payment_method=cash'
            .'&cash_receiver=Export%20Receiver'
            .'&reference=PAYMENT-EXPORT-001';

        $json =
            $this->getJson(
                '/api/reports/payments'
                .$query
            );

        $json
            ->assertOk()
            ->assertJsonPath(
                'summary.payment_count',
                1
            );

        $csv =
            $this->get(
                '/api/reports/payments/csv'
                .$query
            );

        $csv->assertOk();

        $contents =
            $csv->getContent();

        $this->assertStringContainsString(
            'PAYMENT-EXPORT-001',
            $contents
        );

        $this->assertStringNotContainsString(
            'EXCLUDED-PAYMENT',
            $contents
        );
    }

    public function test_payment_report_exports_reject_invalid_filters(): void
    {
        $this->fixture();

        $this->authenticate();

        $this
            ->getJson(
                '/api/reports/payments/pdf'
                .'?from=2026-08-31'
                .'&to=2026-08-01'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to',
            ]);

        $this
            ->getJson(
                '/api/reports/payments/csv'
                .'?payment_method=crypto'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'payment_method',
            ]);
    }

    public function test_payment_report_exports_require_authentication(): void
    {
        $this->fixture();

        $this
            ->getJson(
                '/api/reports/payments/pdf'
            )
            ->assertUnauthorized();

        $this
            ->getJson(
                '/api/reports/payments/csv'
            )
            ->assertUnauthorized();

        $this
            ->getJson(
                '/api/reports/payments/xlsx'
            )
            ->assertUnauthorized();
    }

    public function test_payment_report_exports_are_available_to_all_export_roles(): void
    {
        $this->fixture();

        foreach (
            [
                'administrator',
                'property_manager',
                'viewer',
            ] as $role
        ) {
            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,

                    'is_active' => true,

                    'email_verified_at' => now(),
                ])
            );

            $this
                ->get(
                    '/api/reports/payments/csv'
                )
                ->assertOk();
        }
    }

    public function test_each_payment_report_export_records_one_activity_event(): void
    {
        $this->fixture();

        $user =
            $this->authenticate();

        foreach (
            [
                'pdf',
                'csv',
                'xlsx',
            ] as $format
        ) {
            ActivityLog::query()->delete();

            $this
                ->get(
                    '/api/reports/payments/'
                    .$format
                    .'?payment_method=cash'
                )
                ->assertOk();

            $event =
                ActivityLog::query()
                    ->sole();

            $this->assertSame(
                'report.exported',
                $event->action
            );

            $this->assertSame(
                $user->id,
                $event->user_id
            );

            $this->assertSame(
                'payments',
                $event->metadata['report_type']
            );

            $this->assertSame(
                $format,
                $event->metadata['format']
            );

            $this->assertSame(
                'cash',
                $event->metadata['filters']['payment_method']
            );
        }
    }

    public function test_payment_report_csv_uses_application_language_and_currency(): void
    {
        $this->fixture();

        ApplicationSetting::create([
            'language' => 'fr',

            'currency' => 'FCFA',
        ]);

        $this->authenticate();

        $response =
            $this->get(
                '/api/reports/payments/csv'
            );

        $response->assertOk();

        $contents =
            $response->getContent();

        $this->assertStringContainsString(
            '5 000 FCFA',
            $contents
        );

        $this->assertStringContainsString(
            'Espèces',
            $contents
        );

        $this->assertStringContainsString(
            '15-08-2026',
            $contents
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        $building =
            Building::create([
                'name' => 'Payment Export Building',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Payment Export Unit',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Payment Export Tenant',

                'phone' => '0200098001',

                'email' => 'payment-export@example.test',
            ]);

        PartyRole::create([
            'party_id' => $tenant->id,

            'role' => 'tenant',
        ]);

        $lease =
            Lease::create([
                'unit_id' => $unit->id,

                'tenant_id' => $tenant->id,

                'start_date' => '2026-01-01',

                'rent_amount' => 5000,

                'status' => 'active',
            ]);

        $payment =
            Payment::create([
                'lease_id' => $lease->id,

                'amount' => 5000,

                'payment_date' => '2026-08-15',

                'payment_method' => 'cash',

                'cash_receiver_name' => 'Export Receiver',

                'reference' => 'PAYMENT-EXPORT-001',
            ]);

        return compact(
            'building',
            'unit',
            'tenant',
            'lease',
            'payment'
        );
    }

    private function authenticate(): User
    {
        $user =
            User::factory()->create([
                'role' => 'administrator',

                'is_active' => true,

                'email_verified_at' => now(),
            ]);

        Sanctum::actingAs(
            $user
        );

        return $user;
    }
}

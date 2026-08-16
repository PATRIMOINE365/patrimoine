<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Models\ActivityLog;
use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\SecurityDepositSettlement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentExportActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_pdf_download_is_logged_once(): void
    {
        $context = $this->financialContext();
        $invoice = $this->invoice($context['lease']);

        Sanctum::actingAs($this->administrator());

        $this
            ->get("/api/invoices/{$invoice->id}/pdf")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame('invoice.downloaded', $event->action);
        $this->assertSame('invoice', $event->entity_type);
        $this->assertSame((string) $invoice->id, $event->entity_id);
        $this->assertSame('invoice', $event->metadata['document_type']);
        $this->assertSame('pdf', $event->metadata['format']);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_receipt_pdf_download_is_logged_once(): void
    {
        $context = $this->financialContext();

        $payment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-08-16',
            'payment_method' => 'bank_transfer',
            'reference' => 'REC-N-001',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->get("/api/payments/{$payment->id}/receipt")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame('receipt.downloaded', $event->action);
        $this->assertSame('payment', $event->entity_type);
        $this->assertSame((string) $payment->id, $event->entity_id);
        $this->assertSame('receipt', $event->metadata['document_type']);
        $this->assertSame('pdf', $event->metadata['format']);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_owner_deposit_receipt_download_is_logged_once(): void
    {
        $context = $this->financialContext();

        $transaction = OwnerTransaction::create([
            'owner_account_id' => $context['owner_account']->id,
            'direction' => 'credit',
            'category' => 'owner_deposit',
            'amount' => 4000,
            'transaction_date' => '2026-08-16',
            'payment_method' => 'bank_transfer',
            'deposit_purpose' => 'general_funding',
            'reference' => 'OWNER-DEP-N-001',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->get("/api/owner-deposits/{$transaction->id}/receipt")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'owner_deposit_receipt.downloaded',
            $event->action
        );

        $this->assertSame(
            'owner_transaction',
            $event->entity_type
        );

        $this->assertSame(
            (string) $transaction->id,
            $event->entity_id
        );

        $this->assertSame(
            'owner_deposit_receipt',
            $event->metadata['document_type']
        );

        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_failed_owner_receipt_generation_is_not_logged(): void
    {
        $context = $this->financialContext();

        $transaction = OwnerTransaction::create([
            'owner_account_id' => $context['owner_account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 4000,
            'transaction_date' => '2026-08-16',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->get("/api/owner-deposits/{$transaction->id}/receipt")
            ->assertUnprocessable();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_security_deposit_voucher_download_is_logged_once(): void
    {
        $context = $this->financialContext('terminated');

        $settlement = SecurityDepositSettlement::create([
            'lease_id' => $context['lease']->id,
            'settlement_date' => '2026-08-16',
            'deposit_amount' => 10000,
            'deduction_amount' => 2500,
            'refund_amount' => 7500,
            'tenant_debt_amount' => 0,
            'voucher_number' => 'SDV-N-001',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->get("/api/security-deposit-settlements/{$settlement->id}/voucher")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'security_deposit_voucher.downloaded',
            $event->action
        );

        $this->assertSame(
            'security_deposit_settlement',
            $event->entity_type
        );

        $this->assertSame(
            (string) $settlement->id,
            $event->entity_id
        );

        $this->assertSame(
            'security_deposit_voucher',
            $event->metadata['document_type']
        );
    }

    public function test_owner_report_pdf_and_csv_exports_are_logged(): void
    {
        $context = $this->financialContext();

        Sanctum::actingAs($this->administrator());

        $this
            ->get("/api/reports/owners/{$context['owner']->id}/pdf")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame('report.exported', $event->action);
        $this->assertSame('owner', $event->metadata['report_type']);
        $this->assertSame('pdf', $event->metadata['format']);

        ActivityLog::query()->delete();

        $this
            ->get("/api/reports/owners/{$context['owner']->id}/csv")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame('report.exported', $event->action);
        $this->assertSame('owner', $event->metadata['report_type']);
        $this->assertSame('csv', $event->metadata['format']);
    }

    public function test_all_remaining_report_categories_are_logged(): void
    {
        $context = $this->financialContext();

        Sanctum::actingAs($this->administrator());

        $cases = [
            [
                "/api/reports/buildings/{$context['building']->id}/pdf",
                'building',
                'pdf',
            ],
            [
                "/api/reports/units/{$context['unit']->id}/csv",
                'unit',
                'csv',
            ],
            [
                "/api/reports/tenants/{$context['tenant']->id}/pdf",
                'tenant_statement',
                'pdf',
            ],
            [
                '/api/reports/managing-organisation/csv',
                'managing_organisation',
                'csv',
            ],
        ];

        foreach ($cases as [$uri, $reportType, $format]) {
            ActivityLog::query()->delete();

            $this
                ->get($uri)
                ->assertOk();

            $event = ActivityLog::query()->sole();

            $this->assertSame('report.exported', $event->action);
            $this->assertSame(
                $reportType,
                $event->metadata['report_type']
            );
            $this->assertSame(
                $format,
                $event->metadata['format']
            );
        }
    }

    public function test_json_report_view_is_not_logged(): void
    {
        $context = $this->financialContext();

        Sanctum::actingAs($this->administrator());

        $this
            ->getJson(
                "/api/reports/owners/{$context['owner']->id}"
            )
            ->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_manual_invoice_resend_is_logged_once(): void
    {
        Mail::fake();

        $context = $this->financialContext();
        $invoice = $this->invoice($context['lease']);

        Sanctum::actingAs($this->administrator());

        $this
            ->postJson("/api/invoices/{$invoice->id}/send-email")
            ->assertOk();

        Mail::assertSent(InvoiceMail::class);

        $event = ActivityLog::query()->sole();

        $this->assertSame('invoice.resent', $event->action);
        $this->assertSame('invoice', $event->entity_type);
        $this->assertSame((string) $invoice->id, $event->entity_id);
        $this->assertSame('email', $event->metadata['delivery']);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_manual_receipt_resend_is_logged_once(): void
    {
        Mail::fake();

        $context = $this->financialContext();

        $payment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-08-16',
            'payment_method' => 'bank_transfer',
            'reference' => 'RESEND-REC-N-001',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->postJson("/api/payments/{$payment->id}/send-receipt")
            ->assertOk();

        Mail::assertSent(ReceiptMail::class);

        $event = ActivityLog::query()->sole();

        $this->assertSame('receipt.resent', $event->action);
        $this->assertSame('payment', $event->entity_type);
        $this->assertSame((string) $payment->id, $event->entity_id);
        $this->assertSame('email', $event->metadata['delivery']);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_failed_manual_resends_are_not_logged(): void
    {
        $context = $this->financialContext();

        $context['tenant']->update([
            'email' => null,
        ]);

        $invoice = $this->invoice($context['lease']);

        $payment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-08-16',
            'payment_method' => 'bank_transfer',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->postJson("/api/invoices/{$invoice->id}/send-email")
            ->assertUnprocessable();

        $this
            ->postJson("/api/payments/{$payment->id}/send-receipt")
            ->assertUnprocessable();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_payment_automatic_receipt_email_is_not_manual_resend(): void
    {
        Mail::fake();

        $context = $this->financialContext();
        $this->invoice($context['lease']);

        Sanctum::actingAs($this->administrator());

        $this
            ->postJson('/api/payments', [
                'lease_id' => $context['lease']->id,
                'amount' => 5000,
                'payment_date' => '2026-08-16',
                'payment_method' => 'bank_transfer',
            ])
            ->assertCreated();

        Mail::assertSent(ReceiptMail::class);

        $events = ActivityLog::query()
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $events);

        $this->assertSame(
            'payment.recorded',
            $events->first()->action
        );

        $this->assertFalse(
            $events->contains(
                fn (ActivityLog $event): bool => $event->action === 'receipt.resent'
            )
        );
    }

    public function test_viewer_can_export_and_is_recorded_as_actor(): void
    {
        $context = $this->financialContext();

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $this
            ->get("/api/reports/owners/{$context['owner']->id}/pdf")
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame('report.exported', $event->action);
        $this->assertSame($viewer->id, $event->user_id);
        $this->assertSame(
            UserRole::Viewer->value,
            $event->actor_role
        );
    }

    /**
     * @return array{
     *   building: Building,
     *   unit: Unit,
     *   tenant: Party,
     *   owner: Party,
     *   owner_account: OwnerAccount,
     *   lease: Lease
     * }
     */
    private function financialContext(
        string $leaseStatus = 'active'
    ): array {
        $building = Building::create([
            'name' => 'Activity N Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Activity N Owner',
            'phone' => '0200007001',
            'email' => 'activity-n-owner@example.test',
        ]);

        $owner->roles()->create([
            'role' => 'owner',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $ownerAccount =
            $owner->ownerAccount()->firstOrFail();

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Activity N Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Activity N Tenant',
            'phone' => '0200007002',
            'email' => 'activity-n-tenant@example.test',
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2025-01-01',
            'rent_amount' => 5000,
            'status' => $leaseStatus,
        ]);

        /*
         * Needed by the Managing Organisation report path.
         */
        $managingOrganisation = Party::create([
            'type' => 'organisation',
            'legal_name' => 'Activity N Management Ltd',
            'address' => 'Accra',
            'contact_person_name' => 'Manager',
            'contact_person_phone' => '0300007001',
            'contact_person_email' => 'manager@example.test',
            'phone' => '0300007002',
            'email' => 'office@example.test',
        ]);

        $managingOrganisation->roles()->create([
            'role' => 'managing_organisation',
        ]);

        ApplicationSetting::create([
            'managing_organisation_party_id' => $managingOrganisation->id,
            'default_vat_rate' => 18,
            'language' => 'en',
            'currency' => 'GHS',
        ]);

        return [
            'building' => $building,
            'unit' => $unit,
            'tenant' => $tenant,
            'owner' => $owner,
            'owner_account' => $ownerAccount,
            'lease' => $lease,
        ];
    }

    private function invoice(Lease $lease): Invoice
    {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-N-001',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);
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

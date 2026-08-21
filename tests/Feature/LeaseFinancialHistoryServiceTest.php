<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdjustmentVoucher;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\SecurityDepositApplication;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\WithdrawalReceipt;
use App\Services\LeaseHistory\LeaseFinancialHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaseFinancialHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_contains_pre_cutover_operational_records_without_journal_dependency(): void
    {
        $lease =
            $this->leaseFixture();

        $invoice =
            Invoice::create([
                'lease_id' => $lease->id,

                'invoice_number' => 'INV-HIST-001',

                'type' => 'rent',

                'period_start' => '2026-01-01',

                'period_end' => '2026-01-31',

                'issue_date' => '2026-01-01',

                'due_date' => '2026-01-01',

                'status' => 'issued',

                'total_amount' => 10000,

                'vat_rate' => 18,

                'net_amount' => 8475,

                'vat_amount' => 1525,
            ]);

        $payment =
            Payment::create([
                'lease_id' => $lease->id,

                'amount' => 4000,

                'payment_date' => '2026-01-05',

                'payment_method' => 'bank_transfer',

                'reference' => 'HIST-PAY-001',
            ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,

            'invoice_id' => $invoice->id,

            'amount' => 4000,
        ]);

        $history =
            app(
                LeaseFinancialHistoryService::class
            )->generate(
                $lease
            );

        $types =
            collect(
                $history['events']
            )->pluck(
                'event_type'
            );

        $this->assertTrue(
            $types->contains(
                'invoice'
            )
        );

        $this->assertTrue(
            $types->contains(
                'payment'
            )
        );

        $paymentEvent =
            collect(
                $history['events']
            )->firstWhere(
                'event_type',
                'payment'
            );

        $this->assertSame(
            4000,
            $paymentEvent['amount']
        );

        $this->assertSame(
            '/api/payments/'
            .$payment->id
            .'/receipt',
            $paymentEvent['document']['endpoint']
        );
    }

    public function test_history_is_chronological(): void
    {
        $lease =
            $this->leaseFixture();

        Invoice::create([
            'lease_id' => $lease->id,

            'invoice_number' => 'INV-HIST-002',

            'type' => 'rent',

            'period_start' => '2026-02-01',

            'period_end' => '2026-02-28',

            'issue_date' => '2026-02-01',

            'due_date' => '2026-02-01',

            'status' => 'issued',

            'total_amount' => 5000,

            'vat_rate' => 18,

            'net_amount' => 4237,

            'vat_amount' => 763,
        ]);

        Payment::create([
            'lease_id' => $lease->id,

            'amount' => 1000,

            'payment_date' => '2026-01-15',

            'payment_method' => 'cash',

            'reference' => 'HIST-EARLY',
        ]);

        $history =
            app(
                LeaseFinancialHistoryService::class
            )->generate(
                $lease
            );

        $dates =
            collect(
                $history['events']
            )->pluck(
                'occurred_on'
            )->values()
                ->all();

        $sorted =
            $dates;

        sort(
            $sorted
        );

        $this->assertSame(
            $sorted,
            $dates
        );
    }

    public function test_security_deposit_application_is_one_canonical_history_event(): void
    {
        $lease =
            $this->leaseFixture();

        $invoice =
            Invoice::create([
                'lease_id' => $lease->id,

                'invoice_number' => 'INV-HIST-SD',

                'type' => 'rent',

                'period_start' => '2026-03-01',

                'period_end' => '2026-03-31',

                'issue_date' => '2026-03-01',

                'due_date' => '2026-03-01',

                'status' => 'issued',

                'total_amount' => 50000,

                'vat_rate' => 18,

                'net_amount' => 42373,

                'vat_amount' => 7627,
            ]);

        $account =
            TenantFundAccount::create([
                'lease_id' => $lease->id,

                'type' => 'security_deposit',

                'status' => 'active',
            ]);

        $funding =
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => 'credit',

                'category' => 'deposit_funding',

                'amount' => 120000,

                'transaction_date' => '2026-03-01',

                'reference' => 'SD-HISTORY-FUND',
            ]);

        $applicationMovement =
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => 'debit',

                'category' => 'deposit_deduction',

                'amount' => 20000,

                'transaction_date' => '2026-03-10',

                'reference' => 'SD-HISTORY-APPLY',
            ]);

        SecurityDepositApplication::create([
            'lease_id' => $lease->id,

            'invoice_id' => $invoice->id,

            'tenant_fund_transaction_id' => $applicationMovement->id,

            'amount' => 20000,

            'application_date' => '2026-03-10',

            'notes' => 'Manual application.',
        ]);

        $history =
            app(
                LeaseFinancialHistoryService::class
            )->generate(
                $lease
            );

        $applicationEvents =
            collect(
                $history['events']
            )->where(
                'event_type',
                'security_deposit_application'
            );

        $this->assertCount(
            1,
            $applicationEvents
        );

        $this->assertFalse(
            collect(
                $history['events']
            )->contains(
                fn (array $event): bool => $event['source_type']
                        === 'tenant_fund_transaction'
                    && $event['source_id']
                        === $applicationMovement->id
            )
        );

        /*
         * The original funding movement remains a legitimate distinct
         * financial event and must not disappear.
         */
        $this->assertTrue(
            collect(
                $history['events']
            )->contains(
                fn (array $event): bool => $event['source_type']
                        === 'tenant_fund_transaction'
                    && $event['source_id']
                        === $funding->id
            )
        );
    }

    public function test_withdrawal_receipt_is_one_canonical_history_event_with_document(): void
    {
        $lease =
            $this->leaseFixture();

        $account =
            TenantFundAccount::create([
                'lease_id' => $lease->id,

                'type' => 'consumable_advance',

                'status' => 'active',
            ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,

            'direction' => 'credit',

            'category' => 'advance_funding',

            'amount' => 5000,

            'transaction_date' => '2026-05-01',
        ]);

        $withdrawal =
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => 'debit',

                'category' => 'withdrawal',

                'amount' => 2000,

                'transaction_date' => '2026-05-02',

                'payment_method' => 'bank_transfer',

                'reference' => 'BANK-OUT-001',
            ]);

        $receipt =
            WithdrawalReceipt::create([
                'receipt_number' => 'WDR-HIST-001',

                'tenant_fund_transaction_id' => $withdrawal->id,

                'tenant_fund_account_id' => $account->id,

                'lease_id' => $lease->id,

                'tenant_id' => $lease->tenant_id,

                'fund_type' => 'consumable_advance',

                'amount' => 2000,

                'payment_method' => 'bank_transfer',

                'transaction_date' => '2026-05-02',

                'reference' => 'BANK-OUT-001',

                'tenant_name' => 'History Tenant',

                'lease_label' => 'Lease #'.$lease->id,

                'building_label' => $lease
                    ->unit
                    ->building
                    ->name,

                'unit_label' => $lease->unit->name,

                'performed_by_name' => 'History Operator',
            ]);

        $history =
            app(
                LeaseFinancialHistoryService::class
            )->generate(
                $lease
            );

        $events =
            collect(
                $history['events']
            );

        $withdrawalEvents =
            $events->where(
                'event_type',
                'withdrawal'
            );

        $this->assertCount(
            1,
            $withdrawalEvents
        );

        $event =
            $withdrawalEvents->first();

        $this->assertSame(
            'withdrawal_receipt',
            $event['source_type']
        );

        $this->assertSame(
            'WDR-HIST-001',
            $event['reference']
        );

        $this->assertSame(
            '/api/withdrawal-receipts/'
            .$receipt->id
            .'/pdf',
            $event['document']['endpoint']
        );

        $this->assertSame(
            'bank_transfer',
            $event['payment_method']
        );

        $this->assertFalse(
            $events->contains(
                fn (array $candidate): bool => $candidate['source_type']
                        === 'tenant_fund_transaction'
                    && $candidate['source_id']
                        === $withdrawal->id
            )
        );
    }

    public function test_adjustment_voucher_is_one_canonical_history_event_with_correct_balance_semantics(): void
    {
        $lease =
            $this->leaseFixture();

        $account =
            TenantFundAccount::create([
                'lease_id' => $lease->id,

                'type' => 'rent_reserve',

                'status' => 'active',
            ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,

            'direction' => 'credit',

            'category' => 'reserve_funding',

            'amount' => 10000,

            'transaction_date' => '2026-06-01',
        ]);

        $adjustment =
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => 'debit',

                'category' => 'adjustment',

                'amount' => 2500,

                'transaction_date' => '2026-06-02',

                'notes' => 'Corrected after reconciliation.',
            ]);

        $voucher =
            AdjustmentVoucher::create([
                'voucher_number' => 'ADV-HIST-001',

                'account_type' => 'rent_reserve',

                'account_id' => $account->id,

                'entity_type' => 'tenant',

                'entity_id' => $lease->tenant_id,

                'entity_label' => 'History Tenant — Lease #'
                    .$lease->id,

                'account_label' => 'Rent Reserve',

                'previous_balance' => 10000,

                'corrected_balance' => 7500,

                'difference' => -2500,

                'reason' => 'Corrected after reconciliation.',

                'adjustment_date' => '2026-06-02',

                'performed_by_name' => 'History Operator',

                'source_type' => TenantFundTransaction::class,

                'source_id' => $adjustment->id,
            ]);

        $history =
            app(
                LeaseFinancialHistoryService::class
            )->generate(
                $lease
            );

        $events =
            collect(
                $history['events']
            );

        $adjustmentEvents =
            $events->where(
                'event_type',
                'adjustment'
            );

        $this->assertCount(
            1,
            $adjustmentEvents
        );

        $event =
            $adjustmentEvents->first();

        $this->assertSame(
            'adjustment_voucher',
            $event['source_type']
        );

        $this->assertSame(
            'ADV-HIST-001',
            $event['reference']
        );

        $this->assertSame(
            2500,
            $event['amount']
        );

        $this->assertSame(
            'decrease',
            $event['direction']
        );

        $this->assertSame(
            10000,
            $event['metadata']['previous_balance']
        );

        $this->assertSame(
            7500,
            $event['metadata']['corrected_balance']
        );

        $this->assertSame(
            -2500,
            $event['metadata']['difference']
        );

        $this->assertSame(
            'Corrected after reconciliation.',
            $event['metadata']['reason']
        );

        $this->assertSame(
            '/api/adjustment-vouchers/'
            .$voucher->id
            .'/pdf',
            $event['document']['endpoint']
        );

        $this->assertFalse(
            $events->contains(
                fn (array $candidate): bool => $candidate['source_type']
                        === 'tenant_fund_transaction'
                    && $candidate['source_id']
                        === $adjustment->id
            )
        );
    }

    public function test_financial_history_api_requires_authentication(): void
    {
        $lease =
            $this->leaseFixture();

        $this->getJson(
            '/api/leases/'
            .$lease->id
            .'/financial-history'
        )->assertUnauthorized();
    }

    public function test_financial_history_api_returns_canonical_projection(): void
    {
        $lease =
            $this->leaseFixture();

        $invoice =
            Invoice::create([
                'lease_id' => $lease->id,

                'invoice_number' => 'INV-HISTORY-API-001',

                'type' => 'rent',

                'period_start' => '2026-07-01',

                'period_end' => '2026-07-31',

                'issue_date' => '2026-07-01',

                'due_date' => '2026-07-01',

                'status' => 'issued',

                'total_amount' => 15000,

                'vat_rate' => 18,

                'net_amount' => 12712,

                'vat_amount' => 2288,
            ]);

        Sanctum::actingAs(
            User::factory()->create([
                'role' => 'administrator',
            ])
        );

        $this->getJson(
            '/api/leases/'
            .$lease->id
            .'/financial-history'
        )
            ->assertOk()
            ->assertJsonPath(
                'lease.id',
                $lease->id
            )
            ->assertJsonPath(
                'lease.tenant_id',
                $lease->tenant_id
            )
            ->assertJsonPath(
                'events.0.event_type',
                'invoice'
            )
            ->assertJsonPath(
                'events.0.source_type',
                'invoice'
            )
            ->assertJsonPath(
                'events.0.source_id',
                $invoice->id
            )
            ->assertJsonPath(
                'events.0.reference',
                'INV-HISTORY-API-001'
            )
            ->assertJsonPath(
                'events.0.amount',
                15000
            )
            ->assertJsonPath(
                'events.0.document.endpoint',
                '/api/invoices/'
                .$invoice->id
                .'/pdf'
            );
    }

    public function test_financial_history_api_is_available_to_all_operational_read_roles(): void
    {
        foreach (
            [
                'administrator',
                'property_manager',
                'viewer',
            ] as $role
        ) {
            $lease =
                $this->leaseFixture();

            Sanctum::actingAs(
                User::factory()->create([
                    'role' => $role,
                ])
            );

            $this->getJson(
                '/api/leases/'
                .$lease->id
                .'/financial-history'
            )
                ->assertOk()
                ->assertJsonPath(
                    'lease.id',
                    $lease->id
                );
        }
    }

    public function test_financial_history_exports_generate_pdf_csv_and_xlsx(): void
    {
        $lease =
            $this->leaseFixture();

        Invoice::create([
            'lease_id' => $lease->id,

            'invoice_number' => 'INV-HISTORY-EXPORT-001',

            'type' => 'rent',

            'period_start' => '2026-07-01',

            'period_end' => '2026-07-31',

            'issue_date' => '2026-07-01',

            'due_date' => '2026-07-01',

            'status' => 'issued',

            'total_amount' => 50000,

            'vat_rate' => 18,

            'net_amount' => 42373,

            'vat_amount' => 7627,
        ]);

        $user =
            User::factory()->create([
                'role' => 'administrator',

                'is_active' => true,

                'email_verified_at' => now(),
            ]);

        Sanctum::actingAs(
            $user
        );

        $pdf =
            $this->get(
                '/api/leases/'
                .$lease->id
                .'/financial-history/pdf'
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
                '/api/leases/'
                .$lease->id
                .'/financial-history/csv'
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
            'INV-HISTORY-EXPORT-001',
            $csv->getContent()
        );

        $xlsx =
            $this->get(
                '/api/leases/'
                .$lease->id
                .'/financial-history/xlsx'
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

    public function test_financial_history_exports_require_authentication(): void
    {
        $lease =
            $this->leaseFixture();

        $this->getJson(
            '/api/leases/'
            .$lease->id
            .'/financial-history/pdf'
        )->assertUnauthorized();

        $this->getJson(
            '/api/leases/'
            .$lease->id
            .'/financial-history/csv'
        )->assertUnauthorized();

        $this->getJson(
            '/api/leases/'
            .$lease->id
            .'/financial-history/xlsx'
        )->assertUnauthorized();
    }

    public function test_financial_history_exports_are_available_to_all_report_export_roles(): void
    {
        foreach (
            [
                'administrator',
                'property_manager',
                'viewer',
            ] as $role
        ) {
            $lease =
                $this->leaseFixture(
                    'History Export '
                    .ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $role
                        )
                    )
                    .' Unit'
                );

            $user =
                User::factory()->create([
                    'role' => $role,

                    'is_active' => true,

                    'email_verified_at' => now(),
                ]);

            Sanctum::actingAs(
                $user
            );

            $this->get(
                '/api/leases/'
                .$lease->id
                .'/financial-history/csv'
            )->assertOk();
        }
    }

    public function test_financial_history_export_records_one_activity_event(): void
    {
        $lease =
            $this->leaseFixture();

        $user =
            User::factory()->create([
                'role' => 'administrator',

                'is_active' => true,

                'email_verified_at' => now(),
            ]);

        Sanctum::actingAs(
            $user
        );

        $before =
            ActivityLog::query()
                ->where(
                    'action',
                    'report.exported'
                )
                ->count();

        $this->get(
            '/api/leases/'
            .$lease->id
            .'/financial-history/csv'
        )->assertOk();

        $after =
            ActivityLog::query()
                ->where(
                    'action',
                    'report.exported'
                )
                ->count();

        $this->assertSame(
            $before + 1,
            $after
        );
    }

    public function test_history_is_lease_specific(): void
    {
        $first =
            $this->leaseFixture();

        $second =
            $this->leaseFixture(
                'Second History Unit'
            );

        Payment::create([
            'lease_id' => $first->id,

            'amount' => 1111,

            'payment_date' => '2026-04-01',

            'payment_method' => 'cash',

            'reference' => 'FIRST-ONLY',
        ]);

        Payment::create([
            'lease_id' => $second->id,

            'amount' => 2222,

            'payment_date' => '2026-04-01',

            'payment_method' => 'cash',

            'reference' => 'SECOND-ONLY',
        ]);

        $history =
            app(
                LeaseFinancialHistoryService::class
            )->generate(
                $first
            );

        $references =
            collect(
                $history['events']
            )->pluck(
                'reference'
            );

        $this->assertTrue(
            $references->contains(
                'FIRST-ONLY'
            )
        );

        $this->assertFalse(
            $references->contains(
                'SECOND-ONLY'
            )
        );
    }

    private function leaseFixture(
        string $unitName = 'History Unit'
    ): Lease {
        $owner =
            Party::create([
                'type' => 'person',

                'name' => 'History Owner',

                'phone' => '+233200000001',

                'email' => uniqid(
                    'owner-',
                    true
                )
                    .'@example.test',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'History Tenant',

                'phone' => '+233200000002',

                'email' => uniqid(
                    'tenant-',
                    true
                )
                    .'@example.test',
            ]);

        $tenant->roles()
            ->create([
                'role' => 'tenant',
            ]);

        $owner->roles()
            ->create([
                'role' => 'owner',
            ]);

        $building =
            Building::create([
                'name' => uniqid(
                    'History Building ',
                    true
                ),

                'address' => 'History Address',
            ]);

        BuildingOwner::create([
            'building_id' => $building->id,

            'party_id' => $owner->id,

            'ownership_percentage' => 100,
        ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => $unitName,
            ]);

        return Lease::create([
            'unit_id' => $unit->id,

            'tenant_id' => $tenant->id,

            'status' => 'active',

            'start_date' => '2026-01-01',

            'end_date' => '2026-12-31',

            'rent_amount' => 10000,

            'payment_frequency' => 'monthly',

            'vat_rate' => 18,

            'management_fee_type' => 'none',

            'management_fee_value' => 0,

            'advance_payment_amount' => 0,

            'rent_reserve_amount' => 0,

            'security_deposit_amount' => 0,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.5 Phase 12 financial-system acceptance gate.
 *
 * Detailed behaviour is covered by the dedicated feature/integration suites.
 * This test deliberately verifies the cross-boundary invariants that must
 * remain true when the real Payment HTTP API, FIFO allocation and the
 * post-cutover Financial Journal operate together.
 */
class V105FinancialSystemAcceptanceTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();

        app(SystemChartOfAccounts::class)->install();

        AccountingCutover::query()->create([
            'cutover_key' => AccountingCutover::V105_OPENING_BALANCE,
            'cutover_date' => '2026-08-19',
            'status' => AccountingCutover::STATUS_COMPLETED,
            'position_count' => 0,
            'journal_entry_count' => 0,
            'completed_at' => now(),
            'metadata' => [],
        ]);
    }

    public function test_bank_payment_flows_from_api_through_fifo_into_bank_journal(): void
    {
        [$lease, $oldest, $newer] =
            $this->createScenario('BANK');

        $response = $this->postJson('/api/payments', [
            'lease_id' => $lease->id,
            'amount' => 7000,
            'payment_date' => '2026-08-20',
            'payment_method' => 'bank_transfer',
            'reference' => 'PH12-BANK-001',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('allocated_amount', 7000)
            ->assertJsonPath('unallocated_amount', 0)
            ->assertJsonPath('cash_receiver_user_id', null)
            ->assertJsonPath('cash_receiver_name', null);

        $this->assertSame(
            5000,
            $oldest->fresh()->paidAmount()
        );

        $this->assertSame(
            2000,
            $newer->fresh()->paidAmount()
        );

        $paymentId = (int) $response->json('id');

        $entries = JournalEntry::query()
            ->with('lines')
            ->where(
                'transaction_type',
                AccountingEventMap::EVENT_RENT_RECEIPT
            )
            ->where('snapshot->payment_id', $paymentId)
            ->get();

        $this->assertCount(2, $entries);

        $this->assertSame(
            7000,
            (int) $entries->sum(
                fn (JournalEntry $entry): int =>
                    $entry->debitTotal()
            )
        );

        foreach ($entries as $entry) {
            $this->assertTrue($entry->isBalanced());

            $this->assertTrue(
                $entry->lines->contains(
                    fn ($line): bool =>
                        $line->account_code_snapshot
                            === SystemChartOfAccounts::BANK
                        && $line->debit_amount > 0
                )
            );
        }

        $this->assertAllFinancialJournalEntriesBalance();
    }

    public function test_mobile_payment_flows_from_api_through_fifo_into_mobile_clearing(): void
    {
        [$lease, $oldest, $newer] =
            $this->createScenario('MOMO');

        $response = $this->postJson('/api/payments', [
            'lease_id' => $lease->id,
            'amount' => 7000,
            'payment_date' => '2026-08-20',
            'payment_method' => 'momo',
            'reference' => 'PH12-MOMO-001',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('allocated_amount', 7000)
            ->assertJsonPath('unallocated_amount', 0)
            ->assertJsonPath('cash_receiver_user_id', null)
            ->assertJsonPath('cash_receiver_name', null);

        $this->assertSame(
            5000,
            $oldest->fresh()->paidAmount()
        );

        $this->assertSame(
            2000,
            $newer->fresh()->paidAmount()
        );

        $paymentId = (int) $response->json('id');

        $entries = JournalEntry::query()
            ->with('lines')
            ->where(
                'transaction_type',
                AccountingEventMap::EVENT_RENT_RECEIPT
            )
            ->where('snapshot->payment_id', $paymentId)
            ->get();

        $this->assertCount(2, $entries);

        $this->assertSame(
            7000,
            (int) $entries->sum(
                fn (JournalEntry $entry): int =>
                    $entry->debitTotal()
            )
        );

        foreach ($entries as $entry) {
            $this->assertTrue($entry->isBalanced());

            $this->assertTrue(
                $entry->lines->contains(
                    fn ($line): bool =>
                        $line->account_code_snapshot
                            === SystemChartOfAccounts::MOBILE_PAYMENT_CLEARING
                        && $line->debit_amount > 0
                )
            );
        }

        $this->assertAllFinancialJournalEntriesBalance();
    }

    /**
     * @return array{0: Lease, 1: Invoice, 2: Invoice}
     */
    private function createScenario(
        string $suffix
    ): array {
        $building = Building::create([
            'name' => "Phase 12 {$suffix} Building",
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => "Phase 12 {$suffix} Owner",
            'phone' => '0200001200',
            'email' => strtolower($suffix).'-owner@example.test',
        ]);

        PartyRole::create([
            'party_id' => $owner->id,
            'role' => 'owner',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => "Phase 12 {$suffix} Tenant",
            'phone' => '0200001201',
            'email' => strtolower($suffix).'-tenant@example.test',
        ]);

        PartyRole::create([
            'party_id' => $tenant->id,
            'role' => 'tenant',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-06-01',
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 0,
            'status' => 'active',
        ]);

        $oldest = $this->createInvoice(
            $lease,
            "INV-PH12-{$suffix}-001",
            '2026-06-01'
        );

        $newer = $this->createInvoice(
            $lease,
            "INV-PH12-{$suffix}-002",
            '2026-07-01'
        );

        return [$lease, $oldest, $newer];
    }

    private function createInvoice(
        Lease $lease,
        string $number,
        string $dueDate
    ): Invoice {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $number,
            'period_start' => $dueDate,
            'period_end' => $dueDate,
            'issue_date' => $dueDate,
            'due_date' => $dueDate,
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);
    }

    private function assertAllFinancialJournalEntriesBalance(): void
    {
        $entries = JournalEntry::query()
            ->with('lines')
            ->whereIn('entry_kind', [
                JournalEntry::KIND_FINANCIAL,
                JournalEntry::KIND_REVERSAL,
            ])
            ->get();

        $this->assertNotEmpty($entries);

        foreach ($entries as $entry) {
            $this->assertTrue(
                $entry->isBalanced(),
                "Journal {$entry->journal_number} is not balanced."
            );
        }

        $this->assertSame(
            (int) $entries->sum(
                fn (JournalEntry $entry): int =>
                    $entry->debitTotal()
            ),
            (int) $entries->sum(
                fn (JournalEntry $entry): int =>
                    $entry->creditTotal()
            )
        );
    }
}

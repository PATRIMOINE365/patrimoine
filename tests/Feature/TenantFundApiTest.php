<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TenantFundAccount;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies classification of unapplied tenant Payments into held funds.
 */
class TenantFundApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a Lease with a Payment that has no Invoice allocations.
     *
     * @return array{
     *     lease: Lease,
     *     payment: Payment
     * }
     */
    private function createContext(
        int $paymentAmount = 10000
    ): array {
        $building = Building::create([
            'name' => 'Tenant Fund API Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Tenant Fund API Owner',
            'phone' => '0200000600',
            'email' => 'tenant-fund-owner@example.test',
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
            'name' => 'Tenant Fund API Tenant',
            'phone' => '0200000601',
            'email' => 'tenant-fund-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => $paymentAmount,
            'payment_date' => '2026-08-01',
            'payment_method' => 'bank_transfer',
            'reference' => 'FUND-PAY-001',
        ]);

        return compact(
            'lease',
            'payment'
        );
    }

    /**
     * Unapplied Payment funds may be classified as Rent Reserve.
     */
    public function test_unapplied_payment_can_fund_rent_reserve(): void
    {
        $context = $this->createContext(10000);

        $response = $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'rent_reserve',
                'amount' => 6000,
                'transaction_date' => '2026-08-01',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'reserve_funding'
            )
            ->assertJsonPath(
                'payment.classified_fund_amount',
                6000
            )
            ->assertJsonPath(
                'payment.remaining_unclassified_amount',
                4000
            );

        $account = TenantFundAccount::query()
            ->where('lease_id', $context['lease']->id)
            ->where('type', 'rent_reserve')
            ->firstOrFail();

        $this->assertSame(
            6000,
            $account->balance()
        );
    }

    /**
     * Unapplied money may be split among several tenant fund types.
     */
    public function test_payment_can_be_split_between_reserve_and_advance(): void
    {
        $context = $this->createContext(10000);

        $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'rent_reserve',
                'amount' => 6000,
                'transaction_date' => '2026-08-01',
            ]
        )->assertCreated();

        $response = $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'consumable_advance',
                'amount' => 4000,
                'transaction_date' => '2026-08-01',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'payment.classified_fund_amount',
                10000
            )
            ->assertJsonPath(
                'payment.remaining_unclassified_amount',
                0
            );

        $reserve = TenantFundAccount::query()
            ->where('lease_id', $context['lease']->id)
            ->where('type', 'rent_reserve')
            ->firstOrFail();

        $advance = TenantFundAccount::query()
            ->where('lease_id', $context['lease']->id)
            ->where('type', 'consumable_advance')
            ->firstOrFail();

        $this->assertSame(6000, $reserve->balance());
        $this->assertSame(4000, $advance->balance());
    }

    /**
     * The same Payment money cannot be classified twice.
     */
    public function test_classification_cannot_exceed_remaining_unclassified_amount(): void
    {
        $context = $this->createContext(5000);

        $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'security_deposit',
                'amount' => 4000,
                'transaction_date' => '2026-08-01',
            ]
        )->assertCreated();

        $response = $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'rent_reserve',
                'amount' => 2000,
                'transaction_date' => '2026-08-01',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'amount',
            ]);

        /*
         * Only GHS 1,000 remained after the first classification.
         */
        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            1
        );
    }

    /**
     * Payment money already used to settle Invoices is unavailable for
     * tenant-fund classification.
     */
    public function test_invoice_allocated_money_cannot_be_classified(): void
    {
        $context = $this->createContext(5000);

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-FUND-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'paid',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        PaymentAllocation::create([
            'payment_id' => $context['payment']->id,
            'invoice_id' => $invoice->id,
            'amount' => 5000,
        ]);

        $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'rent_reserve',
                'amount' => 1000,
                'transaction_date' => '2026-08-01',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors([
                'amount',
            ]);

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            0
        );
    }

    /**
     * Security Deposit may be funded from unapplied tenant money.
     */
    public function test_unapplied_payment_can_fund_security_deposit(): void
    {
        $context = $this->createContext(8000);

        $this->postJson(
            "/api/payments/{$context['payment']->id}/tenant-funds",
            [
                'fund_type' => 'security_deposit',
                'amount' => 8000,
                'transaction_date' => '2026-08-01',
                'reference' => 'DEP-FUND-001',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'deposit_funding'
            );

        $account = TenantFundAccount::query()
            ->where('lease_id', $context['lease']->id)
            ->where('type', 'security_deposit')
            ->firstOrFail();

        $this->assertSame(
            8000,
            $account->balance()
        );
    }
}

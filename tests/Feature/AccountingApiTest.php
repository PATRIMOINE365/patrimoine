<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the read-only Accounting API: the managing organisation's own
 * fee income and the VAT it has charged on those fees.
 */
class AccountingApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The organisation's own fee income and VAT liability are
         * administrator-level information, gated by the same
         * capability as the Financial Journal.
         */
        $this->authenticateApiUser(
            UserRole::Administrator->value
        );
    }

    /**
     * Charge a fee and its VAT on a given date.
     */
    private function charge(
        OwnerAccount $account,
        int $fee,
        int $vat,
        string $date
    ): void {
        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'management_fee',
            'amount' => $fee,
            'transaction_date' => $date,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'management_fee_vat',
            'amount' => $vat,
            'transaction_date' => $date,
        ]);
    }

    private function ownerAccount(): OwnerAccount
    {
        $building = Building::create([
            'name' => 'Accounting Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Accounting Owner',
            'phone' => '0200000600',
            'email' => 'accounting-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        return OwnerAccount::query()
            ->where('party_id', $owner->id)
            ->firstOrFail();
    }

    public function test_summary_separates_fee_income_from_vat(): void
    {
        $account = $this->ownerAccount();

        $this->charge($account, 10000, 2000, '2026-03-10');

        $response = $this->getJson('/api/accounting/summary');

        $response->assertOk();

        /*
         * VAT must never be counted as fee income: it is collected on
         * behalf of the tax authority.
         */
        $response->assertJsonPath('totals.management_fee', 10000);
        $response->assertJsonPath('totals.management_fee_vat', 2000);
        $response->assertJsonPath('totals.net_fee_income', 10000);
        $response->assertJsonPath('totals.charged_to_owners', 12000);
    }

    public function test_summary_respects_the_period_filter(): void
    {
        $account = $this->ownerAccount();

        $this->charge($account, 10000, 2000, '2026-03-10');
        $this->charge($account, 5000, 1000, '2026-06-10');

        $response = $this->getJson(
            '/api/accounting/summary?from=2026-06-01&to=2026-06-30'
        );

        $response->assertOk();
        $response->assertJsonPath('totals.management_fee', 5000);
        $response->assertJsonPath('totals.management_fee_vat', 1000);
        $response->assertJsonCount(2, 'transactions');
    }

    public function test_summary_ignores_charges_that_are_not_organisation_income(): void
    {
        $account = $this->ownerAccount();

        $this->charge($account, 10000, 2000, '2026-03-10');

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 100000,
            'transaction_date' => '2026-03-10',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'agent_commission',
            'amount' => 7000,
            'transaction_date' => '2026-03-10',
        ]);

        $response = $this->getJson('/api/accounting/summary');

        $response->assertOk();
        $response->assertJsonPath('totals.charged_to_owners', 12000);
        $response->assertJsonCount(2, 'transactions');
    }

    public function test_summary_rejects_an_inverted_period(): void
    {
        $this->getJson(
            '/api/accounting/summary?from=2026-06-30&to=2026-06-01'
        )->assertUnprocessable();
    }

    public function test_summary_is_denied_to_non_administrators(): void
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

            $this->getJson('/api/accounting/summary')
                ->assertForbidden();
        }
    }
}

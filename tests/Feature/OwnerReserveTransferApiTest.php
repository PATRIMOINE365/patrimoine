<?php

namespace Tests\Feature;

use App\Mail\OwnerReserveTransferVoucherMail;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.8: transfers between the owner's Payout account and
 * Deposit/Expense account.
 */
class OwnerReserveTransferApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * @return array{account: OwnerAccount, building: Building}
     */
    private function createContext(): array
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Reserve Transfer Owner',
            'phone' => '0200007001',
            'email' => 'reserve-owner-'.uniqid().'@example.test',
        ]);

        PartyRole::create([
            'party_id' => $owner->id,
            'role' => 'owner',
        ]);

        $building = Building::create([
            'name' => 'Reserve Transfer Building',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit RT-1',
        ]);

        $account = OwnerAccount::firstOrCreate(
            ['party_id' => $owner->id],
            ['status' => 'active']
        );

        return [
            'account' => $account,
            'building' => $building,
        ];
    }

    private function seedLedger(
        OwnerAccount $account,
        array $rows
    ): void {
        foreach ($rows as [$direction, $category, $amount]) {
            OwnerTransaction::create([
                'owner_account_id' => $account->id,
                'direction' => $direction,
                'category' => $category,
                'amount' => $amount,
                'transaction_date' => '2026-08-01',
            ]);
        }
    }

    public function test_transfer_to_expense_settles_a_negative_deposit_account(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->seedLedger($context['account'], [
            ['credit', 'rent_entitlement', 20000],
            ['debit', 'expense', 5000],
        ]);

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers",
            [
                'direction' => 'to_expense',
                'amount' => 5000,
                'transaction_date' => '2026-08-25',
                'reason' => 'Settle the expense overrun from rent money.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('payout_account_balance', 15000)
            ->assertJsonPath('deposit_account_balance', 0)
            ->assertJsonPath('email_sent', true);

        $reference =
            $response->json('transfer.reference');

        $this->assertMatchesRegularExpression(
            '/^OTR-\d{4}-\d{6}$/',
            $reference
        );

        Mail::assertSent(
            OwnerReserveTransferVoucherMail::class
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'owner_reserve_transfer.recorded',
        ]);

        /*
         * The total balance is unchanged: transfers are internal.
         */
        $this->assertSame(
            15000,
            $context['account']->fresh()->balance()
        );
    }

    public function test_transfer_to_payout_releases_deposit_money(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->seedLedger($context['account'], [
            ['credit', 'owner_deposit', 8000],
        ]);

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers",
            [
                'direction' => 'to_payout',
                'amount' => 3000,
                'transaction_date' => '2026-08-25',
                'reason' => 'Owner requests part of the deposit back.',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('deposit_account_balance', 5000)
            ->assertJsonPath('payout_account_balance', 3000);
    }

    public function test_transfer_cannot_exceed_the_source_account(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->seedLedger($context['account'], [
            ['credit', 'rent_entitlement', 2000],
        ]);

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers",
            [
                'direction' => 'to_expense',
                'amount' => 2001,
                'transaction_date' => '2026-08-25',
                'reason' => 'Overdraw attempt.',
            ]
        )->assertUnprocessable();

        Mail::assertNothingOutgoing();
    }

    public function test_voucher_downloads_and_resends(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->seedLedger($context['account'], [
            ['credit', 'rent_entitlement', 10000],
        ]);

        $transferId = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers",
            [
                'direction' => 'to_expense',
                'amount' => 1000,
                'transaction_date' => '2026-08-25',
                'reason' => 'Voucher round trip.',
            ]
        )
            ->assertCreated()
            ->json('transfer.id');

        $this->get(
            "/api/owner-reserve-transfers/{$transferId}/voucher"
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->postJson(
            "/api/owner-reserve-transfers/{$transferId}/send-email"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Account transfer voucher email sent successfully.'
            );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'owner_reserve_transfer_voucher.resent',
        ]);
    }

    public function test_transfers_are_listed_for_the_owner(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->seedLedger($context['account'], [
            ['credit', 'rent_entitlement', 10000],
        ]);

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers",
            [
                'direction' => 'to_expense',
                'amount' => 1500,
                'transaction_date' => '2026-08-25',
                'reason' => 'Listing test.',
            ]
        )->assertCreated();

        $this->getJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers"
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', 1500)
            ->assertJsonPath('data.0.direction', 'credit');
    }

    public function test_viewer_cannot_record_a_reserve_transfer(): void
    {
        $context = $this->createContext();

        $this->authenticateApiUser('viewer');

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/reserve-transfers",
            [
                'direction' => 'to_expense',
                'amount' => 1000,
                'transaction_date' => '2026-08-25',
                'reason' => 'Viewer attempt.',
            ]
        )->assertForbidden();
    }
}

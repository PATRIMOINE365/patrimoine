<?php

namespace Tests\Feature;

use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies Patrimoine's owner payout rules.
 */
class OwnerPayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an OwnerAccount for payout testing.
     */
    private function createAccount(): OwnerAccount
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Payout Test Owner',
            'phone' => '0200000090',
            'email' => 'payout-owner@example.test',
        ]);

        return OwnerAccount::create([
            'party_id' => $owner->id,
        ]);
    }

    /**
     * A payout reduces the owner's available balance.
     */
    public function test_owner_can_receive_payout_from_available_funds(): void
    {
        $account = $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            7000,
            '2026-08-11',
            'bank_transfer',
            'BANK-PAYOUT-001'
        );

        $this->assertSame(7000, $payout->amount);
        $this->assertSame(7000, $payout->allocatedAmount());
        $this->assertSame(0, $payout->unallocatedAmount());

        /*
         * GHS 10,000 collected - GHS 7,000 payout = GHS 3,000 held.
         */
        $this->assertSame(
            3000,
            $account->fresh()->balance()
        );
    }

    /**
     * A payout may consume funds originating from several credits.
     *
     * This is what allows one owner payment to consolidate rent collected
     * across several Buildings, Units or periods.
     */
    public function test_payout_can_span_multiple_owner_credits(): void
    {
        $account = $this->createAccount();

        $firstCredit = OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 4000,
            'transaction_date' => '2026-06-01',
        ]);

        $secondCredit = OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 6000,
            'transaction_date' => '2026-07-01',
        ]);

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            7000,
            '2026-08-11',
            'momo',
            'MOMO-PAYOUT-001'
        );

        /*
         * FIFO:
         * - first credit:  GHS 4,000
         * - second credit: GHS 3,000
         */
        $this->assertSame(
            4000,
            (int) $payout->allocations()
                ->where('owner_transaction_id', $firstCredit->id)
                ->sum('amount')
        );

        $this->assertSame(
            3000,
            (int) $payout->allocations()
                ->where('owner_transaction_id', $secondCredit->id)
                ->sum('amount')
        );

        $this->assertSame(7000, $payout->allocatedAmount());
    }

    /**
     * Patrimoine must never pay an owner more than the available balance.
     */
    public function test_payout_cannot_exceed_owner_balance(): void
    {
        $account = $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 5000,
            'transaction_date' => '2026-08-01',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Owner payout exceeds available balance.'
        );

        app(OwnerPayoutService::class)->create(
            $account,
            6000,
            '2026-08-11',
            'bank_transfer'
        );
    }

    /**
     * Negative balances are carried forward and cannot be paid out.
     */
    public function test_owner_with_negative_balance_cannot_receive_payout(): void
    {
        $account = $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 3000,
            'transaction_date' => '2026-08-01',
        ]);

        $this->assertSame(-3000, $account->balance());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Owner has no funds available for payout.'
        );

        app(OwnerPayoutService::class)->create(
            $account,
            1000,
            '2026-08-11',
            'cash'
        );
    }

    /**
     * Existing owner debits reduce the amount available for payout.
     */
    public function test_expenses_reduce_payout_capacity(): void
    {
        $account = $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 3000,
            'transaction_date' => '2026-08-05',
        ]);

        /*
         * Only GHS 7,000 remains payable.
         */
        $this->assertSame(7000, $account->balance());

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            7000,
            '2026-08-11',
            'bank_transfer'
        );

        $this->assertSame(0, $account->fresh()->balance());
        $this->assertSame(7000, $payout->allocatedAmount());
    }

    /**
     * Credits already consumed economically by owner expenses must not later
     * be presented as the source of another payout.
     */
    public function test_expenses_consume_oldest_credit_before_payout_allocation(): void
    {
        $account = $this->createAccount();

        /*
        * Old rent credit from one source.
        */
        $oldCredit = OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 5000,
            'transaction_date' => '2026-06-01',
        ]);

        /*
        * GHS 3,000 of that owner's funds is consumed by an expense.
        */
        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 3000,
            'transaction_date' => '2026-06-15',
        ]);

        /*
        * First payout consumes the remaining GHS 2,000 of the old credit.
        */
        $firstPayout = app(OwnerPayoutService::class)->create(
            $account,
            2000,
            '2026-07-01',
            'bank_transfer'
        );

        $this->assertSame(
            2000,
            (int) $firstPayout->allocations()
                ->where('owner_transaction_id', $oldCredit->id)
                ->sum('amount')
        );

        /*
        * New rent later arrives.
        */
        $newCredit = OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 4000,
            'transaction_date' => '2026-08-01',
        ]);

        $secondPayout = app(OwnerPayoutService::class)->create(
            $account,
            4000,
            '2026-08-11',
            'bank_transfer'
        );

        /*
        * V1.0.48 (audit finding 2): the expense above carries no funding
        * source, which means the Deposit/Expense account. It becomes
        * deposit-side DEBT — the business rule explicitly permits that —
        * and does NOT consume the owner's rent money.
        *
        * So of the old GHS 5,000 credit, only the first payout's
        * GHS 2,000 is spoken for: GHS 3,000 of it is still payable, and
        * FIFO attribution draws that down before touching the new
        * credit.
        */
        $this->assertSame(
            3000,
            (int) $secondPayout->allocations()
                ->where('owner_transaction_id', $oldCredit->id)
                ->sum('amount')
        );

        $this->assertSame(
            1000,
            (int) $secondPayout->allocations()
                ->where('owner_transaction_id', $newCredit->id)
                ->sum('amount')
        );

        $this->assertSame(
            0,
            $account->fresh()->balance()
        );
    }
}

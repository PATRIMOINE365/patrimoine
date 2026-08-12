<?php

namespace Tests\Feature;

use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\OwnerLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies direct owner-ledger operations such as deposits and
 * administrative adjustments.
 *
 * Owner deposits represent real money received from an owner and therefore
 * include payment metadata such as payment method and deposit purpose.
 */
class OwnerLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an OwnerAccount for ledger testing.
     */
    private function createAccount(): OwnerAccount
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Owner Ledger Test',
            'phone' => '0200000100',
            'email' => 'owner-ledger@example.test',
        ]);

        return OwnerAccount::create([
            'party_id' => $owner->id,
        ]);
    }

    /**
     * Owner deposits increase the owner's available balance.
     *
     * The transaction must also preserve how and why the money was received.
     */
    public function test_owner_deposit_increases_balance(): void
    {
        $account = $this->createAccount();

        $transaction = app(OwnerLedgerService::class)->recordDeposit(
            account: $account,
            amount: 5000,
            transactionDate: '2026-08-11',
            paymentMethod: 'bank_transfer',
            depositPurpose: 'general_funding',
            reference: 'DEP-001'
        );

        $this->assertSame(
            'credit',
            $transaction->direction
        );

        $this->assertSame(
            'owner_deposit',
            $transaction->category
        );

        $this->assertSame(
            5000,
            $transaction->amount
        );

        $this->assertSame(
            'bank_transfer',
            $transaction->payment_method
        );

        $this->assertSame(
            'general_funding',
            $transaction->deposit_purpose
        );

        $this->assertSame(
            'DEP-001',
            $transaction->reference
        );

        $this->assertSame(
            5000,
            $account->fresh()->balance()
        );
    }

    /**
     * Owner deposits may offset a carried negative balance.
     */
    public function test_owner_deposit_reduces_negative_balance(): void
    {
        $account = $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 7000,
            'transaction_date' => '2026-08-01',
        ]);

        $this->assertSame(
            -7000,
            $account->balance()
        );

        app(OwnerLedgerService::class)->recordDeposit(
            account: $account,
            amount: 5000,
            transactionDate: '2026-08-11',
            paymentMethod: 'bank_transfer',
            depositPurpose: 'general_funding'
        );

        /*
         * GHS -7,000 + GHS 5,000 = GHS -2,000.
         *
         * The remaining negative balance continues to carry forward.
         */
        $this->assertSame(
            -2000,
            $account->fresh()->balance()
        );
    }

    /**
     * Positive manual adjustments increase the owner balance.
     */
    public function test_credit_adjustment_increases_balance(): void
    {
        $account = $this->createAccount();

        $transaction = app(OwnerLedgerService::class)->recordAdjustment(
            $account,
            'credit',
            2000,
            '2026-08-11',
            'Correction of omitted owner credit.',
            'ADJ-001'
        );

        $this->assertSame(
            'credit',
            $transaction->direction
        );

        $this->assertSame(
            'adjustment',
            $transaction->category
        );

        $this->assertSame(
            2000,
            $account->fresh()->balance()
        );
    }

    /**
     * Negative manual adjustments reduce the owner balance and may
     * legitimately create a negative balance.
     */
    public function test_debit_adjustment_reduces_balance(): void
    {
        $account = $this->createAccount();

        app(OwnerLedgerService::class)->recordDeposit(
            account: $account,
            amount: 3000,
            transactionDate: '2026-08-01',
            paymentMethod: 'bank_transfer',
            depositPurpose: 'general_funding'
        );

        app(OwnerLedgerService::class)->recordAdjustment(
            $account,
            'debit',
            5000,
            '2026-08-11',
            'Correction of overstated owner funds.'
        );

        $this->assertSame(
            -2000,
            $account->fresh()->balance()
        );
    }

    /**
     * A deposit cannot be zero or negative.
     */
    public function test_owner_deposit_must_be_positive(): void
    {
        $account = $this->createAccount();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Owner deposit amount must be greater than zero.'
        );

        app(OwnerLedgerService::class)->recordDeposit(
            account: $account,
            amount: 0,
            transactionDate: '2026-08-11',
            paymentMethod: 'bank_transfer',
            depositPurpose: 'general_funding'
        );
    }

    /**
     * An adjustment must use a supported ledger direction.
     */
    public function test_adjustment_requires_valid_direction(): void
    {
        $account = $this->createAccount();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Owner adjustment direction must be credit or debit.'
        );

        app(OwnerLedgerService::class)->recordAdjustment(
            $account,
            'increase',
            1000,
            '2026-08-11',
            'Invalid adjustment direction test.'
        );
    }

    /**
     * Manual adjustments require an explanation for auditability.
     */
    public function test_adjustment_requires_reason(): void
    {
        $account = $this->createAccount();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Owner adjustment reason is required.'
        );

        app(OwnerLedgerService::class)->recordAdjustment(
            $account,
            'credit',
            1000,
            '2026-08-11',
            '   '
        );
    }
}

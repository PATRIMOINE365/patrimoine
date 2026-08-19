<?php

namespace Tests\Feature;

use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashReceiverIdentityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_receiver_identity_columns_exist(): void
    {
        $this->assertTrue(
            Schema::hasColumns('payments', [
                'collector_name',
                'cash_receiver_user_id',
                'cash_receiver_name',
            ])
        );

        $this->assertTrue(
            Schema::hasColumns('owner_transactions', [
                'collector_name',
                'cash_receiver_user_id',
                'cash_receiver_name',
            ])
        );
    }

    public function test_payment_can_preserve_user_and_frozen_receiver_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Cash Receiver Test User',
        ]);

        $payment = new Payment([
            'cash_receiver_user_id' => $user->id,
            'cash_receiver_name' => $user->name,
        ]);

        $this->assertSame(
            $user->id,
            $payment->cash_receiver_user_id
        );

        $this->assertSame(
            'Cash Receiver Test User',
            $payment->cash_receiver_name
        );
    }

    public function test_owner_transaction_can_preserve_user_and_frozen_receiver_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Owner Cash Receiver Test User',
        ]);

        $transaction = new OwnerTransaction([
            'cash_receiver_user_id' => $user->id,
            'cash_receiver_name' => $user->name,
        ]);

        $this->assertSame(
            $user->id,
            $transaction->cash_receiver_user_id
        );

        $this->assertSame(
            'Owner Cash Receiver Test User',
            $transaction->cash_receiver_name
        );
    }

    public function test_payment_cash_receiver_relationship_resolves_user(): void
    {
        $payment = new Payment();

        $relation = $payment->cashReceiver();

        $this->assertSame(
            'cash_receiver_user_id',
            $relation->getForeignKeyName()
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );
    }

    public function test_owner_transaction_cash_receiver_relationship_resolves_user(): void
    {
        $transaction = new OwnerTransaction();

        $relation = $transaction->cashReceiver();

        $this->assertSame(
            'cash_receiver_user_id',
            $relation->getForeignKeyName()
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );
    }

    public function test_legacy_collector_name_remains_mass_assignable_for_history(): void
    {
        $payment = new Payment([
            'collector_name' => 'Historical Collector',
        ]);

        $transaction = new OwnerTransaction([
            'collector_name' => 'Historical Owner Collector',
        ]);

        $this->assertSame(
            'Historical Collector',
            $payment->collector_name
        );

        $this->assertSame(
            'Historical Owner Collector',
            $transaction->collector_name
        );

        $this->assertNull(
            $payment->cash_receiver_user_id
        );

        $this->assertNull(
            $transaction->cash_receiver_user_id
        );
    }
}

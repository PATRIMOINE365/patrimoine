<?php

namespace Tests\Feature;

use App\Http\Requests\StoreOwnerDepositRequest;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashReceiverRuntimeAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_payment_request_does_not_accept_typed_collector_name(): void
    {
        $request = new StorePaymentRequest();

        $this->assertArrayNotHasKey(
            'collector_name',
            $request->rules()
        );
    }

    public function test_owner_deposit_request_does_not_accept_typed_collector_name(): void
    {
        $request = new StoreOwnerDepositRequest();

        $this->assertArrayNotHasKey(
            'collector_name',
            $request->rules()
        );
    }

    public function test_payment_has_normalized_cash_receiver_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('payments', [
                'cash_receiver_user_id',
                'cash_receiver_name',
            ])
        );
    }

    public function test_owner_transaction_has_normalized_cash_receiver_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('owner_transactions', [
                'cash_receiver_user_id',
                'cash_receiver_name',
            ])
        );
    }

    public function test_historical_opening_cash_uses_snapshot_without_user_relationship(): void
    {
        $service = app(\App\Services\LeaseInitializationService::class);

        $reflection = new \ReflectionClass($service);

        $source = file_get_contents(
            app_path('Services/LeaseInitializationService.php')
        );

        $this->assertStringContainsString(
            "'cash_receiver_user_id' => null",
            $source
        );

        $this->assertStringContainsString(
            "'advance_received_collector'",
            $source
        );

        $this->assertStringContainsString(
            "'cash_receiver_name' =>",
            $source
        );

        $this->assertStringNotContainsString(
            "'collector_name' => \$data[",
            $source
        );

        $this->assertSame(
            \App\Services\LeaseInitializationService::class,
            $reflection->getName()
        );
    }

    public function test_historical_opening_payment_does_not_infer_current_user(): void
    {
        $source = file_get_contents(
            app_path('Services/LeaseInitializationService.php')
        );

        $this->assertStringNotContainsString(
            "Auth::user()",
            $source
        );

        $this->assertStringNotContainsString(
            "auth()->user()",
            $source
        );

        $this->assertStringContainsString(
            "'cash_receiver_user_id' => null",
            $source
        );
    }

}

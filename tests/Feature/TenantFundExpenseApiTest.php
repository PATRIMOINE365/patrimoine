<?php

namespace Tests\Feature;

use App\Mail\TenantFundExpenseVoucherMail;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.8: lease-specific expenses settled from tenant fund accounts.
 */
class TenantFundExpenseApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * @return array{account: TenantFundAccount}
     */
    private function fundedAccount(
        string $type = 'consumable_advance',
        int $amount = 10000
    ): TenantFundAccount {
        $building = Building::create([
            'name' => 'Tenant Expense Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit TE-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Tenant Expense Tenant',
            'phone' => '0200008001',
            'email' => 'tenant-expense-'.uniqid().'@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => $type,
            'status' => 'active',
        ]);

        if ($amount > 0) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'direction' => 'credit',
                'category' => match ($type) {
                    'rent_reserve' => 'reserve_funding',
                    'consumable_advance' => 'advance_funding',
                    'security_deposit' => 'deposit_funding',
                },
                'amount' => $amount,
                'transaction_date' => '2026-08-01',
            ]);
        }

        return $account;
    }

    public function test_expense_is_recorded_with_voucher_and_email(): void
    {
        Mail::fake();

        $account = $this->fundedAccount();

        $response = $this->postJson(
            '/api/tenant-fund-expenses',
            [
                'tenant_fund_account_id' => $account->id,
                'amount' => 4000,
                'transaction_date' => '2026-08-25',
                'payment_method' => 'bank_transfer',
                'description' => 'Plumbing repair billed to tenant funds.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('account_balance', 6000)
            ->assertJsonPath('email_sent', true);

        $this->assertMatchesRegularExpression(
            '/^TEX-\d{6}$/',
            $response->json('expense.reference')
        );

        Mail::assertSent(
            TenantFundExpenseVoucherMail::class
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'tenant_expense.recorded',
        ]);
    }

    public function test_expense_can_never_overdraw_the_source_account(): void
    {
        Mail::fake();

        $account = $this->fundedAccount(
            type: 'security_deposit',
            amount: 3000
        );

        $this->postJson(
            '/api/tenant-fund-expenses',
            [
                'tenant_fund_account_id' => $account->id,
                'amount' => 3001,
                'transaction_date' => '2026-08-25',
                'payment_method' => 'cash',
                'description' => 'Overdraw attempt.',
            ]
        )->assertUnprocessable();

        Mail::assertNothingSent();

        $this->assertSame(
            3000,
            $account->fresh()->balance()
        );
    }

    public function test_voucher_downloads_and_resends(): void
    {
        Mail::fake();

        $account = $this->fundedAccount();

        $expenseId = $this->postJson(
            '/api/tenant-fund-expenses',
            [
                'tenant_fund_account_id' => $account->id,
                'amount' => 1000,
                'transaction_date' => '2026-08-25',
                'payment_method' => 'momo',
                'description' => 'Voucher round trip.',
            ]
        )
            ->assertCreated()
            ->json('expense.id');

        $this->get(
            "/api/tenant-fund-expenses/{$expenseId}/voucher"
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->postJson(
            "/api/tenant-fund-expenses/{$expenseId}/send-email"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Tenant expense voucher email sent successfully.'
            );
    }

    public function test_viewer_cannot_record_a_tenant_expense(): void
    {
        $account = $this->fundedAccount();

        $this->authenticateApiUser('viewer');

        $this->postJson(
            '/api/tenant-fund-expenses',
            [
                'tenant_fund_account_id' => $account->id,
                'amount' => 1000,
                'transaction_date' => '2026-08-25',
                'payment_method' => 'cash',
                'description' => 'Viewer attempt.',
            ]
        )->assertForbidden();
    }
}

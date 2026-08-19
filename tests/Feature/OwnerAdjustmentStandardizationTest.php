<?php

namespace Tests\Feature;

use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class OwnerAdjustmentStandardizationTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();

        CarbonImmutable::setTestNow(
            '2026-08-19 12:00:00'
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_owner_adjustment_uses_correct_balance_semantics(): void
    {
        $account =
            $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                'owner_deposit',

            'amount' =>
                5000,

            'transaction_date' =>
                '2026-08-01',
        ]);

        $response =
            $this->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' =>
                        7200,

                    'reason' =>
                        'Correct verified Owner balance.',

                    'reference' =>
                        'ADJ-V105-001',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'adjustment.previous_balance',
                5000
            )
            ->assertJsonPath(
                'adjustment.corrected_balance',
                7200
            )
            ->assertJsonPath(
                'adjustment.difference',
                2200
            )
            ->assertJsonPath(
                'transaction.direction',
                'credit'
            )
            ->assertJsonPath(
                'transaction.amount',
                2200
            )
            ->assertJsonPath(
                'transaction.transaction_date',
                '2026-08-19T00:00:00.000000Z'
            )
            ->assertJsonPath(
                'owner_account.balance',
                7200
            );

        $this->assertSame(
            7200,
            $account
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_adjustment_may_correct_balance_negative(): void
    {
        $account =
            $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                'owner_deposit',

            'amount' =>
                3000,

            'transaction_date' =>
                '2026-08-01',
        ]);

        $this
            ->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' =>
                        -2000,

                    'reason' =>
                        'Verified Owner deficit.',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'transaction.direction',
                'debit'
            )
            ->assertJsonPath(
                'transaction.amount',
                5000
            )
            ->assertJsonPath(
                'adjustment.difference',
                -5000
            )
            ->assertJsonPath(
                'owner_account.balance',
                -2000
            );
    }

    public function test_owner_adjustment_cannot_be_backdated_by_request(): void
    {
        $account =
            $this->createAccount();

        $this
            ->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' =>
                        1000,

                    'reason' =>
                        'Today only.',

                    'transaction_date' =>
                        '2020-01-01',

                    'direction' =>
                        'debit',

                    'amount' =>
                        999999,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'transaction.direction',
                'credit'
            )
            ->assertJsonPath(
                'transaction.amount',
                1000
            )
            ->assertJsonPath(
                'owner_account.balance',
                1000
            );

        $transaction =
            OwnerTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->sole();

        $this->assertSame(
            '2026-08-19',
            $transaction
                ->transaction_date
                ->toDateString()
        );
    }

    public function test_owner_adjustment_requires_corrected_balance(): void
    {
        $account =
            $this->createAccount();

        $this
            ->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'reason' =>
                        'Missing balance.',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'corrected_balance',
            ]);
    }

    public function test_owner_adjustment_requires_reason(): void
    {
        $account =
            $this->createAccount();

        $this
            ->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' =>
                        1000,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'reason',
            ]);
    }

    public function test_zero_difference_creates_no_adjustment_transaction(): void
    {
        $account =
            $this->createAccount();

        OwnerTransaction::create([
            'owner_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                'owner_deposit',

            'amount' =>
                1000,

            'transaction_date' =>
                '2026-08-01',
        ]);

        $this
            ->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' =>
                        1000,

                    'reason' =>
                        'No correction required.',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'owner_account',
            ]);

        $this->assertDatabaseMissing(
            'owner_transactions',
            [
                'owner_account_id' =>
                    $account->id,

                'category' =>
                    'adjustment',
            ]
        );
    }

    public function test_activity_log_contains_standardized_balance_context(): void
    {
        $account =
            $this->createAccount();

        $this
            ->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' =>
                        -500,

                    'reason' =>
                        'Verified reconciliation difference.',
                ]
            )
            ->assertCreated();

        $event =
            \App\Models\ActivityLog::query()
                ->where(
                    'action',
                    'owner_adjustment.recorded'
                )
                ->sole();

        $this->assertSame(
            0,
            $event->snapshot[
                'previous_balance'
            ]
        );

        $this->assertSame(
            -500,
            $event->snapshot[
                'corrected_balance'
            ]
        );

        $this->assertSame(
            -500,
            $event->snapshot[
                'difference'
            ]
        );

        $this->assertSame(
            'Verified reconciliation difference.',
            $event->snapshot[
                'reason'
            ]
        );
    }

    private function createAccount(): OwnerAccount
    {
        $owner =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 4 Step 3 Owner',

                'phone' =>
                    '0200004300',

                'email' =>
                    'phase4-step3-owner@example.test',
            ]);

        return OwnerAccount::create([
            'party_id' =>
                $owner->id,
        ]);
    }
}

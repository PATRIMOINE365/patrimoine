<?php

namespace Tests\Feature;

use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\Documents\OwnerPayoutBreakdownService;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The payout receipt has to show how its figure was reached.
 *
 * It used to show the amount, a count of the ledger rows the payout
 * consumed, and the balance left — so an owner holding it could not check
 * it against anything. What they ask is what came in since they last
 * collected, what was taken off it, and how that reaches the number.
 *
 * The rule these tests hold to is that the arithmetic must ALWAYS close:
 * brought forward + received − deducted − payouts = carried forward, for
 * any ledger, including one holding a movement the receipt has no name
 * for.
 */
class OwnerPayoutBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private function createAccount(): OwnerAccount
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Breakdown Owner',
            'phone' => '0200000091',
            'email' => 'breakdown-owner@example.test',
        ]);

        return OwnerAccount::create([
            'party_id' => $owner->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function movement(
        OwnerAccount $account,
        string $direction,
        string $category,
        int $amount,
        string $date
    ): OwnerTransaction {
        return OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => $direction,
            'category' => $category,
            'amount' => $amount,
            'transaction_date' => $date,
        ]);
    }

    /**
     * The figure on the receipt, taken apart.
     */
    public function test_the_breakdown_explains_the_amount_paid(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 14200, '2026-08-05');
        $this->movement($account, 'credit', 'owner_deposit', 500, '2026-08-06');
        $this->movement($account, 'debit', 'expense', 2100, '2026-08-07');
        $this->movement($account, 'debit', 'management_fee', 1704, '2026-08-08');
        $this->movement($account, 'debit', 'management_fee_vat', 256, '2026-08-08');
        $this->movement($account, 'debit', 'agent_commission', 154, '2026-08-09');

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            10486,
            '2026-08-10',
            'bank_transfer',
            'BANK-BREAKDOWN-001'
        );

        $breakdown = app(OwnerPayoutBreakdownService::class)
            ->forPayout($payout);

        $this->assertNotNull($breakdown);

        $this->assertSame(14700, $breakdown['received_total']);
        $this->assertSame(4214, $breakdown['deducted_total']);
        $this->assertSame(0, $breakdown['brought_forward']);
        $this->assertSame(10486, $breakdown['available']);
        $this->assertSame(10486, $breakdown['amount']);
        $this->assertSame(0, $breakdown['carried_forward']);

        $received = collect($breakdown['received'])
            ->pluck('amount', 'key')
            ->all();

        $this->assertSame(
            ['rent_entitlement' => 14200, 'owner_deposits' => 500],
            $received
        );

        $deducted = collect($breakdown['deducted'])
            ->pluck('amount', 'key')
            ->all();

        $this->assertSame(
            [
                'expenses' => 2100,
                'management_fees' => 1704,
                'management_fee_vat' => 256,
                'agent_commissions' => 154,
            ],
            $deducted
        );
    }

    /**
     * The period is what happened since the owner last collected, not the
     * whole life of the account.
     */
    public function test_the_period_starts_after_the_previous_payout(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 9000, '2026-06-01');

        $first = app(OwnerPayoutService::class)->create(
            $account,
            5000,
            '2026-06-30',
            'cash',
            'CASH-1'
        );

        $this->movement($account, 'credit', 'rent_entitlement', 6000, '2026-07-15');
        $this->movement($account, 'debit', 'management_fee', 600, '2026-07-20');

        $second = app(OwnerPayoutService::class)->create(
            $account,
            9400,
            '2026-07-31',
            'cash',
            'CASH-2'
        );

        $service = app(OwnerPayoutBreakdownService::class);

        $breakdown = $service->forPayout($second);

        $this->assertTrue($breakdown['has_previous_payout']);
        $this->assertSame('2026-07-01', $breakdown['from']);
        $this->assertSame('2026-07-31', $breakdown['to']);

        /*
         * Only July's movements are in the period; June's rent and the
         * June payout are inside the brought-forward figure.
         */
        $this->assertSame(6000, $breakdown['received_total']);
        $this->assertSame(600, $breakdown['deducted_total']);
        $this->assertSame(4000, $breakdown['brought_forward']);
        $this->assertSame(9400, $breakdown['available']);
        $this->assertSame(0, $breakdown['carried_forward']);

        /* The first payout has nothing before it to carry forward from. */
        $firstBreakdown = $service->forPayout($first);

        $this->assertFalse($firstBreakdown['has_previous_payout']);
        $this->assertNull($firstBreakdown['from']);
        $this->assertSame(0, $firstBreakdown['brought_forward']);
        $this->assertSame(9000, $firstBreakdown['received_total']);
    }

    /**
     * A movement the receipt has no name for must still be counted.
     *
     * The totals are the period's own credit and debit sums rather than
     * the sum of the lines printed, so an unnamed category is shown as
     * "Other" instead of quietly falling out of the reconciliation and
     * leaving an owner with a receipt that does not add up.
     */
    public function test_an_unnamed_movement_is_shown_rather_than_dropped(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 5000, '2026-08-01');
        $this->movement($account, 'credit', 'adjustment', 250, '2026-08-02');
        $this->movement($account, 'debit', 'adjustment', 100, '2026-08-03');

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            5150,
            '2026-08-04',
            'cash',
            'CASH-ADJ'
        );

        $breakdown = app(OwnerPayoutBreakdownService::class)
            ->forPayout($payout);

        $this->assertSame(5250, $breakdown['received_total']);
        $this->assertSame(100, $breakdown['deducted_total']);

        $this->assertSame(
            $breakdown['brought_forward']
            + $breakdown['received_total']
            - $breakdown['deducted_total'],
            $breakdown['available']
        );

        $this->assertSame(
            $breakdown['available']
            - $breakdown['amount']
            - $breakdown['other_payouts'],
            $breakdown['carried_forward']
        );
    }

    /**
     * Whatever the ledger holds, the receipt must close.
     */
    public function test_the_reconciliation_always_closes(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 20000, '2026-08-01');
        $this->movement($account, 'credit', 'owner_deposit', 3000, '2026-08-02');
        $this->movement($account, 'credit', 'adjustment', 400, '2026-08-03');
        $this->movement($account, 'debit', 'expense', 1200, '2026-08-04');
        $this->movement($account, 'debit', 'management_fee', 2000, '2026-08-05');
        $this->movement($account, 'debit', 'management_fee_vat', 300, '2026-08-05');
        $this->movement($account, 'debit', 'agent_commission', 500, '2026-08-06');
        $this->movement($account, 'debit', 'adjustment', 150, '2026-08-07');

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            15000,
            '2026-08-08',
            'bank_transfer',
            'BANK-CLOSE'
        );

        $breakdown = app(OwnerPayoutBreakdownService::class)
            ->forPayout($payout);

        $this->assertSame(
            $breakdown['brought_forward']
            + $breakdown['received_total']
            - $breakdown['deducted_total'],
            $breakdown['available'],
            'Brought forward plus receipts less deductions is not the available figure.'
        );

        $this->assertSame(
            $breakdown['available']
            - $breakdown['amount']
            - $breakdown['other_payouts'],
            $breakdown['carried_forward'],
            'The balance carried forward does not follow from the payout.'
        );

        /* And it agrees with the ledger itself. */
        $this->assertSame(
            $account->fresh()->balance(),
            $breakdown['carried_forward']
        );
    }

    /**
     * The receipt renders it.
     */
    public function test_the_receipt_prints_the_workings(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 8000, '2026-08-01');
        $this->movement($account, 'debit', 'management_fee', 800, '2026-08-02');

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            7200,
            '2026-08-03',
            'cash',
            'CASH-PRINT'
        );

        $markup = file_get_contents(
            resource_path('views/documents/owner-payout-receipt.blade.php')
        );

        foreach (
            [
                'money_in',
                'deducted',
                'reconciliation',
                'brought_forward',
                'available',
                'this_payout',
                'carried_forward',
            ] as $key
        ) {
            $this->assertStringContainsString(
                'owner_payout_receipt.'.$key,
                $markup,
                'The receipt does not print '.$key.'.'
            );
        }

        $contents = app(
            \App\Services\Documents\OwnerPayoutReceiptDocumentService::class
        )->generate($payout);

        $this->assertStringStartsWith('%PDF', $contents);
    }
}

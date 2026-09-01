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
        $this->assertSame(2114, $breakdown['deductions_total']);
        $this->assertSame(2100, $breakdown['expenses_total']);
        $this->assertSame(0, $breakdown['brought_forward']);
        $this->assertSame(10486, $breakdown['available']);
        $this->assertSame(10486, $breakdown['amount']);
        $this->assertSame(0, $breakdown['carried_forward']);

        $this->assertSame(
            [14200, 500],
            collect($breakdown['received'])->pluck('amount')->all(),
            'Each receipt is its own row, in the order it happened.'
        );

        $this->assertSame(
            [1, 2],
            collect($breakdown['received'])->pluck('number')->all()
        );

        $this->assertSame(
            ['fee', 'vat', 'commission'],
            collect($breakdown['deductions'])->pluck('label')->all()
        );

        $this->assertSame(
            [1704, 256, 154],
            collect($breakdown['deductions'])->pluck('amount')->all()
        );

        $this->assertSame(
            [2100],
            collect($breakdown['expenses'])->pluck('amount')->all()
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

        /*
         * V1.0.47: the period is bounded by when the previous payout was
         * RECORDED, and the label names that payout by its own date —
         * "since the payout of 30 June" — rather than by the day after
         * it. Membership was never really a date question; pretending it
         * was is what let a backdated movement into a closed receipt.
         */
        $this->assertSame('2026-06-30', $breakdown['from']);
        $this->assertSame('2026-07-31', $breakdown['to']);

        /*
         * Only July's movements are in the period; June's rent and the
         * June payout are inside the brought-forward figure.
         */
        $this->assertSame(6000, $breakdown['received_total']);
        $this->assertSame(600, $breakdown['deductions_total']);
        $this->assertSame(0, $breakdown['expenses_total']);
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
        $this->assertSame(100, $breakdown['deductions_total']);
        $this->assertSame(0, $breakdown['expenses_total']);

        $this->assertSame(
            $breakdown['brought_forward']
            + $breakdown['received_total']
            - $breakdown['deductions_total']
            - $breakdown['expenses_total'],
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
            - $breakdown['deductions_total']
            - $breakdown['expenses_total'],
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
     * Every movement in the period is in exactly one table.
     *
     * This is what lets an owner add the three tables up and arrive at
     * the payout. A category that fell into none of them would leave the
     * summary saying one thing and the evidence under it another.
     */
    public function test_every_movement_lands_in_exactly_one_table(): void
    {
        $account = $this->createAccount();

        $movements = [
            ['credit', 'rent_entitlement', 8000],
            ['credit', 'owner_deposit', 1000],
            ['credit', 'adjustment', 200],
            ['credit', 'reserve_transfer', 300],
            ['debit', 'management_fee', 800],
            ['debit', 'management_fee_vat', 120],
            ['debit', 'agent_commission', 240],
            ['debit', 'adjustment', 60],
            ['debit', 'reserve_transfer', 80],
            ['debit', 'expense', 1500],
        ];

        foreach ($movements as $index => [$direction, $category, $amount]) {
            $this->movement(
                $account,
                $direction,
                $category,
                $amount,
                '2026-08-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)
            );
        }

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            5700,
            '2026-08-20',
            'cash',
            'CASH-ALL'
        );

        $breakdown = app(OwnerPayoutBreakdownService::class)
            ->forPayout($payout);

        /*
         * Ten movements, and the payout is not one of the ten.
         *
         * V1.0.48 (audit finding 3): the two reserve transfers moved to
         * their own zero-effect table — a transfer is the owner's money
         * changing pockets, so it belongs in neither money-in nor
         * money-out.
         */
        $this->assertCount(3, $breakdown['received']);
        $this->assertCount(4, $breakdown['deductions']);
        $this->assertCount(1, $breakdown['expenses']);
        $this->assertCount(2, $breakdown['transfers']);

        foreach (['received', 'deductions', 'expenses', 'transfers'] as $table) {
            $this->assertSame(
                $breakdown[$table.'_total'],
                collect($breakdown[$table])->sum('amount'),
                'The '.$table.' table does not add up to its own total.'
            );

            $this->assertSame(
                range(1, count($breakdown[$table])),
                collect($breakdown[$table])->pluck('number')->all(),
                'The '.$table.' rows are not numbered from one.'
            );
        }

        $this->assertSame(
            $breakdown['brought_forward']
            + $breakdown['received_total']
            - $breakdown['deductions_total']
            - $breakdown['expenses_total'],
            $breakdown['available']
        );
    }

    /**
     * A rent row names the unit and the period the rent was for.
     */
    public function test_a_rent_row_carries_its_unit_and_its_period(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 4000, '2026-08-01');

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            4000,
            '2026-08-05',
            'cash',
            'CASH-ROW'
        );

        $row = app(OwnerPayoutBreakdownService::class)
            ->forPayout($payout)['received'][0];

        /*
         * Without an invoice behind it there is no period to show, so the
         * row falls back to the date the money moved rather than printing
         * an empty range.
         */
        $this->assertNull($row['from']);
        $this->assertSame('2026-08-01', $row['date']);
        $this->assertArrayHasKey('place', $row);
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
                'summary',
                'received_table',
                'deductions_table',
                'expenses_table',
                'brought_forward',
                'total_received',
                'total_deductions',
                'total_expenses',
                'available',
                'this_payout',
                'carried_forward',
            ] as $key
        ) {
            /*
             * The three table headings are composed rather than written
             * out — the tables share one loop — so the key is looked for
             * on its own as well as fully qualified.
             */
            $this->assertTrue(
                str_contains($markup, 'owner_payout_receipt.'.$key)
                || str_contains($markup, "'".$key."'"),
                'The receipt does not print '.$key.'.'
            );
        }

        $contents = app(
            \App\Services\Documents\OwnerPayoutReceiptDocumentService::class
        )->generate($payout);

        $this->assertStringStartsWith('%PDF', $contents);
    }
}

<?php

namespace Tests\Feature;

use App\Models\OwnerAccount;
use App\Models\OwnerPayout;
use App\Models\OwnerPayoutAllocation;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\Documents\OwnerPayoutBreakdownService;
use App\Services\OwnerLedgerProjection;
use App\Services\OwnerPayoutAllocationEngine;
use App\Services\OwnerPayoutService;
use App\Services\OwnerReserveTransferService;
use App\Services\Reports\OwnerReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * V1.0.48: the owner accounting rebuild — regressions for audit
 * findings 2, 3 and 4 of 2026-09-01, and the release conditions the
 * rebuild was agreed against.
 *
 * The shape of the rebuild: OwnerLedgerProjection classifies every
 * movement once and computes payout, deposit and total INDEPENDENTLY
 * (total = payout + deposit is now a real check, not a tautology);
 * OwnerPayoutAllocationEngine replays the ledger keeping the two pools
 * apart with every portion remembering its origin; and statements show
 * internal transfers separately with zero effect on the consolidated
 * arithmetic.
 */
class OwnerAccountingRebuildTest extends TestCase
{
    use RefreshDatabase;

    /*
    |----------------------------------------------------------------------
    | Finding 2 — valid withdrawals no longer fail.
    |----------------------------------------------------------------------
    */

    /**
     * The audit's live trigger: deposit-side debt beside independently
     * available payout money. The old attribution subtracted the
     * expense from the rent credits and refused a perfectly valid
     * withdrawal in front of the customer.
     */
    public function test_deposit_side_debt_does_not_block_a_valid_withdrawal(): void
    {
        $account = $this->createAccount();

        $rent = $this->movement($account, 'credit', 'rent_entitlement', 42500, '2026-08-01');

        /*
         * A deposit-funded expense with NO deposits behind it: the
         * deposit side goes to −20,000, which the business rules
         * explicitly permit as debt the owner owes the agency.
         */
        $this->movement($account, 'debit', 'expense', 20000, '2026-08-05');

        $this->assertSame(42500, $account->payoutAccountBalance());
        $this->assertSame(-20000, $account->depositAccountBalance());
        $this->assertSame(22500, $account->balance());

        /*
         * The withdrawal the old shortcut refused.
         */
        $payout = app(OwnerPayoutService::class)->create(
            $account,
            42500,
            '2026-08-10',
            'bank_transfer'
        );

        $this->assertSame(
            42500,
            (int) $payout->allocations()->sum('amount')
        );

        $this->assertSame(
            42500,
            (int) $payout->allocations()
                ->where('owner_transaction_id', $rent->id)
                ->sum('amount')
        );

        $account = $account->fresh();

        $this->assertSame(0, $account->payoutAccountBalance());
        $this->assertSame(-20000, $account->depositAccountBalance());
    }

    /**
     * The fail-closed safeguard stays: an amount beyond the payout
     * side is refused even while the consolidated total would cover it.
     */
    public function test_deposit_money_is_still_not_withdrawable(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 1000, '2026-08-01');
        $this->movement($account, 'credit', 'owner_deposit', 5000, '2026-08-02');

        $this->assertSame(1000, $account->payoutAccountBalance());
        $this->assertSame(6000, $account->balance());

        $this->expectException(RuntimeException::class);

        app(OwnerPayoutService::class)->create(
            $account,
            2000,
            '2026-08-10',
            'cash'
        );
    }

    /*
    |----------------------------------------------------------------------
    | Origin preservation through reserve transfers.
    |----------------------------------------------------------------------
    */

    /**
     * Deposit → release → payout: the receipt must be able to say the
     * money came from a returned deposit rather than from rent. The
     * allocation therefore points at the DEPOSIT credit, carried
     * through the transfer with its origin intact.
     */
    public function test_a_released_deposit_keeps_its_origin_through_to_the_payout(): void
    {
        $account = $this->createAccount();

        $deposit = $this->movement($account, 'credit', 'owner_deposit', 5000, '2026-08-01');

        app(OwnerReserveTransferService::class)->transfer(
            $account->fresh(),
            'to_payout',
            5000,
            '2026-08-05',
            'Deposit released for payout.'
        );

        $payout = app(OwnerPayoutService::class)->create(
            $account->fresh(),
            5000,
            '2026-08-10',
            'bank_transfer'
        );

        $this->assertSame(
            5000,
            (int) $payout->allocations()
                ->where('owner_transaction_id', $deposit->id)
                ->sum('amount')
        );
    }

    /**
     * Transfers both ways, repeatedly, conserve money: the total never
     * moves, the pools always sum to it, and what returns to the payout
     * side is withdrawable again.
     */
    public function test_repeated_transfers_conserve_the_total(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 10000, '2026-08-01');

        $transfers = app(OwnerReserveTransferService::class);

        $transfers->transfer($account->fresh(), 'to_expense', 4000, '2026-08-02', 'Set aside.');
        $transfers->transfer($account->fresh(), 'to_payout', 1500, '2026-08-03', 'Released.');
        $transfers->transfer($account->fresh(), 'to_expense', 500, '2026-08-04', 'Set aside again.');

        $account = $account->fresh();

        $this->assertSame(10000, $account->balance());
        $this->assertSame(7000, $account->payoutAccountBalance());
        $this->assertSame(3000, $account->depositAccountBalance());

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            7000,
            '2026-08-10',
            'cash'
        );

        $this->assertSame(
            7000,
            (int) $payout->allocations()->sum('amount')
        );
    }

    /*
    |----------------------------------------------------------------------
    | Partial and repeated payouts; expense reversals; backdating.
    |----------------------------------------------------------------------
    */

    public function test_partial_payouts_consume_credits_fifo_without_double_counting(): void
    {
        $account = $this->createAccount();

        $first = $this->movement($account, 'credit', 'rent_entitlement', 3000, '2026-08-01');
        $second = $this->movement($account, 'credit', 'rent_entitlement', 4000, '2026-08-02');

        $payouts = app(OwnerPayoutService::class);

        $one = $payouts->create($account->fresh(), 2000, '2026-08-05', 'cash');
        $two = $payouts->create($account->fresh(), 2500, '2026-08-06', 'cash');
        $three = $payouts->create($account->fresh(), 2500, '2026-08-07', 'cash');

        /*
         * 2000 from the first credit; 1000 + 1500 from first/second;
         * the rest of the second. Every credit consumed exactly once.
         */
        $this->assertSame(2000, $this->allocated($one, $first));
        $this->assertSame(1000, $this->allocated($two, $first));
        $this->assertSame(1500, $this->allocated($two, $second));
        $this->assertSame(2500, $this->allocated($three, $second));

        $this->assertSame(
            7000,
            (int) OwnerPayoutAllocation::query()->sum('amount')
        );

        $this->assertSame(0, $account->fresh()->payoutAccountBalance());

        $this->expectException(RuntimeException::class);

        $payouts->create($account->fresh(), 1, '2026-08-08', 'cash');
    }

    /**
     * A cancelled expense payment restores the pool its funding source
     * names — without duplicating money.
     */
    public function test_an_expense_reversal_restores_the_funding_position(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 10000, '2026-08-01');

        /*
         * A payout-funded expense payment, later cancelled by its
         * reversal credit in the same category and funding source.
         */
        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'expense',
            'funding_source' => 'payout_account',
            'amount' => 4000,
            'transaction_date' => '2026-08-02',
        ]);

        $this->assertSame(6000, $account->fresh()->payoutAccountBalance());

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'expense',
            'funding_source' => 'payout_account',
            'amount' => 4000,
            'transaction_date' => '2026-08-03',
        ]);

        $account = $account->fresh();

        $this->assertSame(10000, $account->payoutAccountBalance());
        $this->assertSame(0, $account->depositAccountBalance());

        /*
         * And the restored money is genuinely withdrawable.
         */
        $payout = app(OwnerPayoutService::class)->create(
            $account,
            10000,
            '2026-08-10',
            'bank_transfer'
        );

        $this->assertSame(
            10000,
            (int) $payout->allocations()->sum('amount')
        );
    }

    /**
     * A backdated entry recorded after a payout must not retroactively
     * reassign what that payout was attributed to.
     */
    public function test_backdated_entries_do_not_reassign_an_issued_payout(): void
    {
        $account = $this->createAccount();

        $rent = $this->movement($account, 'credit', 'rent_entitlement', 5000, '2026-08-10');

        $payout = app(OwnerPayoutService::class)->create(
            $account->fresh(),
            5000,
            '2026-08-15',
            'cash'
        );

        $before = $payout->allocations()
            ->orderBy('id')
            ->get(['owner_transaction_id', 'amount'])
            ->toArray();

        /*
         * Rent arrives later carrying an EARLIER date. It must fund
         * future payouts, not rewrite the finished one.
         */
        $backdated = $this->movement($account, 'credit', 'rent_entitlement', 2000, '2026-08-01');

        $second = app(OwnerPayoutService::class)->create(
            $account->fresh(),
            2000,
            '2026-08-20',
            'cash'
        );

        $this->assertSame(
            $before,
            $payout->fresh()->allocations()
                ->orderBy('id')
                ->get(['owner_transaction_id', 'amount'])
                ->toArray()
        );

        $this->assertSame(2000, $this->allocated($second, $backdated));
        $this->assertSame(0, $this->allocated($second, $rent));
    }

    /*
    |----------------------------------------------------------------------
    | Finding 3 — statements and receipts against internal transfers.
    |----------------------------------------------------------------------
    */

    public function test_the_owner_statement_gives_transfers_zero_consolidated_effect(): void
    {
        $account = $this->createAccount();
        $owner = $account->party;

        $this->movement($account, 'credit', 'rent_entitlement', 8000, '2026-08-01');

        app(OwnerReserveTransferService::class)->transfer(
            $account->fresh(),
            'to_expense',
            3000,
            '2026-08-05',
            'Set aside for repairs.'
        );

        $report = app(OwnerReportService::class)->generate(
            $owner,
            '2026-08-03',
            '2026-08-31'
        );

        /*
         * The transfer happened inside the period; the rent before it.
         * Opening balance counts the rent alone, the period's
         * consolidated credits and debits count nothing — the transfer
         * is the owner's money changing pockets — and the closing
         * balance equals the opening one. The transfer still SHOWS on
         * its own line.
         */
        $this->assertSame(8000, $report['summary']['opening_balance']);
        $this->assertSame(0, $report['summary']['credits']);
        $this->assertSame(0, $report['summary']['debits']);
        $this->assertSame(8000, $report['summary']['closing_balance']);
        $this->assertSame(3000, $report['summary']['reserve_transfers_credit']);
    }

    public function test_the_frozen_receipt_shows_transfers_outside_the_arithmetic(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 9000, '2026-08-01');

        app(OwnerReserveTransferService::class)->transfer(
            $account->fresh(),
            'to_expense',
            2000,
            '2026-08-02',
            'Set aside.'
        );

        $payout = app(OwnerPayoutService::class)->create(
            $account->fresh(),
            7000,
            '2026-08-10',
            'bank_transfer'
        );

        $statement = $payout->fresh()->statement;

        $this->assertSame(9000, $statement['received_total']);
        $this->assertSame(0, $statement['deductions_total']);
        $this->assertSame(9000, $statement['available']);
        $this->assertSame(2000, $statement['carried_forward']);

        $this->assertCount(1, $statement['transfers']);
        $this->assertSame(2000, $statement['transfers'][0]['amount']);
        $this->assertSame('reserve_out', $statement['transfers'][0]['label']);

        $this->assertSame(
            OwnerPayoutBreakdownService::CALCULATION_VERSION,
            $statement['calculation_version']
        );

        /*
         * The receipt's closing figure and the ledger's own net
         * position at the same boundary are one answer.
         */
        $this->assertSame(
            $account->fresh()->balance(),
            $statement['carried_forward']
        );
    }

    /**
     * A statement frozen by the previous calculation stays exactly as
     * it was frozen: issued documents are never silently rewritten.
     */
    public function test_a_version_one_statement_is_returned_untouched(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 5000, '2026-08-01');

        $payout = app(OwnerPayoutService::class)->create(
            $account->fresh(),
            5000,
            '2026-08-10',
            'cash'
        );

        $frozen = [
            'from' => null,
            'to' => '2026-08-10',
            'brought_forward' => 0,
            'received_total' => 5000,
            'deductions_total' => 0,
            'expenses_total' => 0,
            'available' => 5000,
            'amount' => 5000,
            'other_payouts' => 0,
            'carried_forward' => 0,
            'received' => [],
            'deductions' => [],
            'expenses' => [],
        ];

        $payout->forceFill(['statement' => $frozen])->save();

        $this->assertSame(
            $frozen,
            app(OwnerPayoutBreakdownService::class)
                ->forPayout($payout->fresh())
        );
    }

    /*
    |----------------------------------------------------------------------
    | The projection's invariant and the engine's conservation.
    |----------------------------------------------------------------------
    */

    public function test_the_projection_refuses_an_unclassified_category(): void
    {
        $projection = app(OwnerLedgerProjection::class);

        $this->expectException(\LogicException::class);

        $projection->classify('mystery_money', 'credit', null, 100);
    }

    public function test_every_category_the_application_writes_is_classified(): void
    {
        /*
         * The guard that makes the classification EXHAUSTIVE: every
         * category literal the application writes into the owner ledger
         * must appear in the projection's table. A new category added
         * without deciding which pool it belongs to fails here, at
         * build time, rather than disagreeing at a customer.
         */
        $written = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                base_path('app'),
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            if (! str_contains($source, 'OwnerTransaction::create')) {
                continue;
            }

            /*
             * Only categories inside OwnerTransaction::create([...])
             * blocks: the same file may write tenant-fund rows whose
             * categories belong to a different ledger entirely.
             */
            preg_match_all(
                '/OwnerTransaction::create\(\[(.*?)\]\)/s',
                $source,
                $blocks
            );

            foreach ($blocks[1] as $block) {
                preg_match_all(
                    "/'category'\s*=>\s*'([a-z_]+)'/",
                    $block,
                    $matches
                );

                foreach ($matches[1] as $category) {
                    $written[$category] = true;
                }
            }
        }

        $this->assertNotEmpty($written);

        $projection = app(OwnerLedgerProjection::class);

        foreach (array_keys($written) as $category) {
            foreach (['credit', 'debit'] as $direction) {
                $effect = $projection->classify(
                    $category,
                    $direction,
                    null,
                    100
                );

                $this->assertSame(
                    $projection->totalEffect($category, $direction, 100),
                    $effect['payout'] + $effect['deposit'],
                    "Category {$category} {$direction} splits into the"
                    .' pools differently than into the total.'
                );
            }
        }
    }

    public function test_allocations_conserve_and_never_exceed_their_credit(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 3333, '2026-08-01');
        $this->movement($account, 'credit', 'rent_entitlement', 6667, '2026-08-02');
        $this->movement($account, 'debit', 'management_fee', 1000, '2026-08-03');

        $payouts = app(OwnerPayoutService::class);

        $payouts->create($account->fresh(), 4000, '2026-08-05', 'cash');
        $payouts->create($account->fresh(), 5000, '2026-08-06', 'cash');

        /*
         * Conservation: total allocated equals total paid out, and no
         * credit is allocated beyond its own amount.
         */
        $this->assertSame(
            9000,
            (int) OwnerPayoutAllocation::query()->sum('amount')
        );

        $credits = OwnerTransaction::query()
            ->where('direction', 'credit')
            ->get();

        foreach ($credits as $credit) {
            $this->assertLessThanOrEqual(
                (int) $credit->amount,
                (int) $credit->payoutAllocations()->sum('amount')
            );

            $this->assertGreaterThanOrEqual(
                0,
                (int) $credit->payoutAllocations()->min('amount')
            );
        }

        $this->assertSame(0, $account->fresh()->payoutAccountBalance());
    }

    public function test_the_engine_and_the_projection_agree_on_a_busy_ledger(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 8000, '2026-08-01');
        $this->movement($account, 'credit', 'owner_deposit', 3000, '2026-08-02');
        $this->movement($account, 'debit', 'management_fee', 800, '2026-08-03');
        $this->movement($account, 'debit', 'management_fee_vat', 160, '2026-08-03');
        $this->movement($account, 'debit', 'expense', 2500, '2026-08-04');
        $this->movement($account, 'credit', 'adjustment', 300, '2026-08-05');

        app(OwnerReserveTransferService::class)->transfer(
            $account->fresh(),
            'to_payout',
            500,
            '2026-08-06',
            'Released.'
        );

        $pools = app(OwnerPayoutAllocationEngine::class)
            ->poolsFor((int) $account->id);

        $balances = app(OwnerLedgerProjection::class)
            ->balancesFor((int) $account->id);

        $this->assertSame(
            $balances['payout'],
            $pools['payout_available'] - $pools['payout_deficit']
        );

        $this->assertSame(
            $balances['deposit'],
            $pools['deposit_available'] - $pools['deposit_deficit']
        );

        $this->assertSame(
            $balances['total'],
            $balances['payout'] + $balances['deposit']
        );
    }

    /*
    |----------------------------------------------------------------------
    | Helpers
    |----------------------------------------------------------------------
    */

    private function createAccount(): OwnerAccount
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Rebuild Owner',
            'phone' => '0200000092',
            'email' => 'rebuild-owner@example.test',
        ]);

        return OwnerAccount::create([
            'party_id' => $owner->id,
        ]);
    }

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

    private function allocated(
        OwnerPayout $payout,
        OwnerTransaction $credit
    ): int {
        return (int) $payout->allocations()
            ->where('owner_transaction_id', $credit->id)
            ->sum('amount');
    }
}

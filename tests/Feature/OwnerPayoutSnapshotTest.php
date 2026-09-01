<?php

namespace Tests\Feature;

use App\Models\OwnerAccount;
use App\Models\OwnerPayout;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\Documents\OwnerPayoutBreakdownService;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A completed payout is a historical record.
 *
 * Patrimoine allows a transaction to be recorded today carrying a date
 * from May, and that is legitimate. What is not legitimate is a payout
 * already made changing because of it. The receipt answers what the
 * owner's account held when the money was released; it does not answer
 * what the database happens to contain now.
 *
 * The case these tests are written from is a real one, from production:
 * a payout of 10,486 was made, a lease and its payment were entered
 * afterwards with an earlier date, and the 10,486 receipt absorbed them
 * while the 2,782 payout that actually released them showed nothing at
 * all.
 */
class OwnerPayoutSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function createAccount(): OwnerAccount
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Snapshot Owner',
            'phone' => '0200000097',
            'email' => 'snapshot-owner@example.test',
        ]);

        return OwnerAccount::create([
            'party_id' => $owner->id,
        ]);
    }

    /**
     * A movement, recorded at a stated moment.
     *
     * The recording moment is what the whole rule turns on, so the tests
     * set it explicitly rather than relying on how quickly they run.
     */
    private function movement(
        OwnerAccount $account,
        string $direction,
        string $category,
        int $amount,
        string $date,
        string $recordedAt
    ): OwnerTransaction {
        $movement = OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => $direction,
            'category' => $category,
            'amount' => $amount,
            'transaction_date' => $date,
        ]);

        $movement->forceFill(['created_at' => $recordedAt])->saveQuietly();

        return $movement->refresh();
    }

    private function payout(
        OwnerAccount $account,
        int $amount,
        string $date,
        string $recordedAt
    ): OwnerPayout {
        Carbon::setTestNow($recordedAt);

        $payout = app(OwnerPayoutService::class)->create(
            $account,
            $amount,
            $date,
            'bank_transfer',
            null,
            null
        );

        Carbon::setTestNow();

        return $payout->refresh();
    }

    /**
     * The production case, end to end.
     */
    public function test_money_recorded_after_a_payout_belongs_to_the_next_one(): void
    {
        $account = $this->createAccount();

        /* What was known when the first payout was made. */
        $this->movement($account, 'credit', 'rent_entitlement', 11500, '2026-08-01', '2026-08-31 09:00:00');
        $this->movement($account, 'debit', 'management_fee', 900, '2026-08-01', '2026-08-31 09:00:00');
        $this->movement($account, 'debit', 'management_fee_vat', 114, '2026-08-01', '2026-08-31 09:00:00');

        $first = $this->payout($account, 10486, '2026-08-31', '2026-08-31 13:52:57');

        /*
         * The lease that had not been entered yet. Its money is dated
         * MAY — three months before the payout above — and is recorded
         * hours after it.
         */
        $this->movement($account, 'credit', 'rent_entitlement', 3000, '2026-05-15', '2026-08-31 20:34:06');
        $this->movement($account, 'debit', 'management_fee', 180, '2026-05-15', '2026-08-31 20:34:06');
        $this->movement($account, 'debit', 'management_fee_vat', 38, '2026-05-15', '2026-08-31 20:34:06');

        $second = $this->payout($account, 2782, '2026-08-31', '2026-08-31 20:55:27');

        $breakdown = app(OwnerPayoutBreakdownService::class);

        $firstStatement = $breakdown->forPayout($first->refresh());
        $secondStatement = $breakdown->forPayout($second);

        /* The first receipt is untouched by what came later. */
        $this->assertSame(11500, $firstStatement['received_total']);
        $this->assertSame(1014, $firstStatement['deductions_total']);
        $this->assertSame(10486, $firstStatement['available']);
        $this->assertSame(10486, $firstStatement['amount']);
        $this->assertSame(0, $firstStatement['carried_forward']);

        $this->assertCount(1, $firstStatement['received']);

        /* The second receipt explains its own figure. */
        $this->assertSame(0, $secondStatement['brought_forward']);
        $this->assertSame(3000, $secondStatement['received_total']);
        $this->assertSame(218, $secondStatement['deductions_total']);
        $this->assertSame(2782, $secondStatement['available']);
        $this->assertSame(2782, $secondStatement['amount']);
        $this->assertSame(0, $secondStatement['carried_forward']);

        $this->assertCount(1, $secondStatement['received']);
        $this->assertCount(2, $secondStatement['deductions']);
    }

    /**
     * Two payouts on one day are two different payouts.
     *
     * The date window could not tell them apart: the second one's period
     * ran from the day after the first to the same date, which is empty,
     * so its receipt showed a figure and no workings whatsoever.
     */
    public function test_two_payouts_on_the_same_day_each_show_their_own_money(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 5000, '2026-08-10', '2026-08-31 08:00:00');

        $first = $this->payout($account, 5000, '2026-08-31', '2026-08-31 10:00:00');

        $this->movement($account, 'credit', 'rent_entitlement', 4000, '2026-08-12', '2026-08-31 14:00:00');

        $second = $this->payout($account, 4000, '2026-08-31', '2026-08-31 16:00:00');

        $breakdown = app(OwnerPayoutBreakdownService::class);

        $secondStatement = $breakdown->forPayout($second);

        $this->assertSame(4000, $secondStatement['received_total']);
        $this->assertNotEmpty(
            $secondStatement['received'],
            'A payout made on the same day as another still has to show its own money.'
        );
        $this->assertSame(0, $secondStatement['brought_forward']);
        $this->assertSame(4000, $secondStatement['amount']);

        $this->assertSame(5000, $breakdown->forPayout($first->refresh())['received_total']);
    }

    /**
     * The statement is written down, not worked out again.
     */
    public function test_the_composition_is_frozen_onto_the_payout(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 7000, '2026-08-01', '2026-08-20 09:00:00');

        $payout = $this->payout($account, 7000, '2026-08-20', '2026-08-20 12:00:00');

        $this->assertIsArray($payout->statement);
        $this->assertSame(7000, $payout->statement['received_total']);
        $this->assertNotNull($payout->statement_frozen_at);

        /*
         * Something recorded afterwards, dated before. The frozen
         * statement must not move.
         */
        $this->movement($account, 'credit', 'rent_entitlement', 999, '2026-08-02', '2026-09-05 09:00:00');

        $this->assertSame(
            7000,
            app(OwnerPayoutBreakdownService::class)
                ->forPayout($payout->refresh())['received_total'],
            'A frozen statement must not change because the ledger did.'
        );
    }

    /**
     * Two payouts entered in the same second are still two payouts.
     *
     * Timestamps are stored to the second, so a seeded account, an import
     * or somebody quick produces payouts that look simultaneous. Found on
     * pre-production, where the demonstration data made two payouts in
     * one second and the first claimed every row up to it — leaving the
     * second with an empty statement, which is the fault this whole
     * release exists to remove.
     *
     * The boundary is therefore never earlier than the payout's own
     * ledger debit.
     */
    public function test_payouts_recorded_in_the_same_second_are_still_separated(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 5000, '2026-08-10', '2026-08-31 16:09:32');

        $first = $this->payout($account, 5000, '2026-08-31', '2026-08-31 16:09:32');

        $this->movement($account, 'credit', 'rent_entitlement', 4000, '2026-08-12', '2026-08-31 16:09:32');

        $second = $this->payout($account, 4000, '2026-08-31', '2026-08-31 16:09:32');

        /*
         * Both statements are thrown away and composed again, which is
         * what the backfill does to a payout made before freezing
         * existed: nothing but the timestamps to go on.
         */
        foreach ([$first, $second] as $payout) {
            $payout->forceFill([
                'statement' => null,
                'statement_frozen_at' => null,
            ])->saveQuietly();
        }

        $breakdown = app(OwnerPayoutBreakdownService::class);

        $firstStatement = $breakdown->forPayout($first->refresh());
        $secondStatement = $breakdown->forPayout($second->refresh());

        $this->assertSame(5000, $firstStatement['received_total']);
        $this->assertSame(5000, $firstStatement['amount']);

        $this->assertSame(
            4000,
            $secondStatement['received_total'],
            'A payout entered in the same second as another still has its own money.'
        );
        $this->assertNotEmpty($secondStatement['received']);
        $this->assertSame(4000, $secondStatement['amount']);
        $this->assertSame(0, $secondStatement['carried_forward']);
    }

    /**
     * The arithmetic closes, whatever is in the window.
     */
    public function test_the_statement_always_reconciles(): void
    {
        $account = $this->createAccount();

        $this->movement($account, 'credit', 'rent_entitlement', 9000, '2026-07-01', '2026-07-31 09:00:00');
        $this->movement($account, 'debit', 'expense', 1200, '2026-07-05', '2026-07-31 09:00:00');
        $this->movement($account, 'debit', 'management_fee', 800, '2026-07-06', '2026-07-31 09:00:00');

        $first = $this->payout($account, 5000, '2026-07-31', '2026-07-31 12:00:00');

        $this->movement($account, 'credit', 'rent_entitlement', 4000, '2026-08-01', '2026-08-31 09:00:00');
        $this->movement($account, 'debit', 'expense', 500, '2026-08-02', '2026-08-31 09:00:00');

        $second = $this->payout($account, 3000, '2026-08-31', '2026-08-31 12:00:00');

        foreach ([$first->refresh(), $second] as $payout) {
            $statement = app(OwnerPayoutBreakdownService::class)->forPayout($payout);

            $this->assertSame(
                $statement['available'],
                $statement['brought_forward']
                + $statement['received_total']
                - $statement['deductions_total']
                - $statement['expenses_total'],
                'Brought forward plus the tables must be the available figure.'
            );

            $this->assertSame(
                $statement['carried_forward'],
                $statement['available']
                - $statement['amount']
                - $statement['other_payouts'],
                'Available less what was paid must be what is carried forward.'
            );
        }

        /* The second picks up exactly what the first left behind. */
        $firstStatement = app(OwnerPayoutBreakdownService::class)->forPayout($first);
        $secondStatement = app(OwnerPayoutBreakdownService::class)->forPayout($second);

        $this->assertSame(
            $firstStatement['carried_forward'],
            $secondStatement['brought_forward'],
            'One payout carries forward into the next.'
        );
    }
}

<?php

namespace App\Services;

use App\Models\Building;
use App\Models\OwnerAccount;
use App\Models\OwnerExpense;
use App\Models\OwnerExpenseBill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records expense bills charged DIRECTLY to one owner.
 *
 * Patrimoine business rules:
 *
 * - A bill contains one or more expense lines (description + amount).
 * - Every line is charged 100% to the billed OwnerAccount; there is no
 *   Building context and therefore no ownership-percentage allocation.
 * - V1.0.8: recording a bill creates the OEB- document and its lines
 *   only. The owner ledger debit and Financial Journal posting happen
 *   per explicit payment in OwnerExpenseBillPaymentService, and can be
 *   cancelled again.
 * - The whole batch is atomic: either the complete bill exists with all
 *   of its lines, or nothing does.
 */
class OwnerExpenseBillingService
{
    public function __construct(
        private readonly OwnerExpenseBillNumberService $billNumbers
    ) {
    }

    /**
     * Record a validated batch of expense lines as one owner bill.
     *
     * @param  array<int, array{description: mixed, amount: mixed}>  $lines
     *
     * @throws RuntimeException When the batch violates billing rules.
     */
    public function record(
        OwnerAccount $ownerAccount,
        array $lines,
        string $billDate,
        ?string $notes,
        User $actor,
        ?int $buildingId = null
    ): OwnerExpenseBill {
        return DB::transaction(function () use (
            $ownerAccount,
            $lines,
            $billDate,
            $notes,
            $buildingId
        ): OwnerExpenseBill {
            /*
             * Lock the owner account row so concurrent financial
             * operations against the same owner serialize instead of
             * interleaving ledger writes.
             */
            $ownerAccount = OwnerAccount::query()
                ->lockForUpdate()
                ->findOrFail($ownerAccount->id);

            $lines = array_values($lines);

            if ($lines === []) {
                throw new RuntimeException(
                    'An owner expense bill requires at least one expense line.'
                );
            }

            /*
             * Validate every line before writing anything, so a bad line
             * can never leave a partially recorded bill behind.
             */
            foreach ($lines as $line) {
                $description = trim(
                    (string) ($line['description'] ?? '')
                );

                if (
                    $description === ''
                    || mb_strlen($description) > 255
                ) {
                    throw new RuntimeException(
                        'Every expense line requires a description of at most 255 characters.'
                    );
                }

                $amount = $line['amount'] ?? null;

                if (
                    ! is_int($amount)
                    || $amount <= 0
                ) {
                    throw new RuntimeException(
                        'Every expense line requires a whole amount greater than zero.'
                    );
                }
            }

            $totalAmount = array_sum(
                array_map(
                    fn (array $line): int =>
                        (int) $line['amount'],
                    $lines
                )
            );

            /*
             * The bill number is issued under its own lock so concurrent
             * bills can never collide on the printed identity.
             */
            $billNumber =
                $this->billNumbers->next();

            $bill = OwnerExpenseBill::create([
                'owner_account_id' => $ownerAccount->id,
                'bill_number' => $billNumber,
                'bill_date' => $billDate,
                'total_amount' => $totalAmount,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $description = trim(
                    (string) $line['description']
                );

                $amount = (int) $line['amount'];

                /*
                 * Direct-to-owner billed expense line.
                 *
                 * NULL building/unit context is the defining marker of a
                 * billed expense (vs. a Building-allocated expense).
                 *
                 * V1.0.8: recording a bill no longer debits the owner
                 * ledger or posts to the Journal. The bill stays unpaid
                 * until it is explicitly settled through the Pay flow
                 * (OwnerExpenseBillPaymentService), which records the
                 * ledger debit and Journal entry per payment.
                 */
                OwnerExpense::create([
                    'building_id' => $buildingId,
                    'unit_id' => null,
                    'owner_expense_bill_id' => $bill->id,
                    'description' => $description,
                    'amount' => $amount,
                    'expense_date' => $billDate,
                    'reference' => $billNumber,
                ]);
            }

            return $bill
                ->refresh()
                ->load('expenses');
        });
    }

    /**
     * V1.0.8: split one set of expense lines across every owner of a
     * Building according to ownership percentage.
     *
     * Each co-owner receives their own bill whose line amounts are the
     * prorated shares (largest-remainder rounding, so every line's
     * shares sum exactly to the entered amount). Owners whose share of
     * every line rounds to zero receive no bill.
     *
     * @param  array<int, array{description: mixed, amount: mixed}>  $lines
     * @return array<int, OwnerExpenseBill>
     */
    public function recordSplit(
        Building $building,
        array $lines,
        string $billDate,
        ?string $notes,
        User $actor
    ): array {
        return DB::transaction(function () use (
            $building,
            $lines,
            $billDate,
            $notes,
            $actor
        ): array {
            $building = Building::query()
                ->with(['ownerships.party'])
                ->lockForUpdate()
                ->findOrFail($building->id);

            $ownerships = $building->ownerships
                ->sortBy('id')
                ->values();

            if ($ownerships->isEmpty()) {
                throw new RuntimeException(
                    __('business.owner.no_ownership')
                );
            }

            $totalPercentage = (float) $ownerships->sum(
                fn ($ownership): float => (float) $ownership->ownership_percentage
            );

            if ($totalPercentage <= 0) {
                throw new RuntimeException(
                    __('business.owner.no_ownership')
                );
            }

            /*
             * Prorate every line independently so each bill's lines sum
             * exactly to that owner's share and, across owners, to the
             * entered line amount.
             */
            $perOwnerLines = [];

            foreach (array_values($lines) as $line) {
                $amount = (int) $line['amount'];

                $shares = [];
                $fractions = [];
                $allocated = 0;

                foreach ($ownerships as $index => $ownership) {
                    $exact = $amount
                        * (float) $ownership->ownership_percentage
                        / $totalPercentage;

                    $shares[$index] = (int) floor($exact);
                    $fractions[$index] = $exact - floor($exact);
                    $allocated += $shares[$index];
                }

                $remainder = $amount - $allocated;

                arsort($fractions);

                foreach (array_keys($fractions) as $index) {
                    if ($remainder <= 0) {
                        break;
                    }

                    $shares[$index] += 1;
                    $remainder -= 1;
                }

                foreach ($shares as $index => $share) {
                    if ($share <= 0) {
                        continue;
                    }

                    $perOwnerLines[$index][] = [
                        'description' => $line['description'],
                        'amount' => $share,
                    ];
                }
            }

            $bills = [];

            foreach ($ownerships as $index => $ownership) {
                if (empty($perOwnerLines[$index])) {
                    continue;
                }

                $account = OwnerAccount::firstOrCreate(
                    ['party_id' => $ownership->party_id],
                    ['status' => 'active']
                );

                $shareNote = sprintf(
                    '%s (%s%%)',
                    trim((string) ($notes ?? '')) !== ''
                        ? trim((string) $notes)
                        : $building->name,
                    rtrim(rtrim(number_format(
                        (float) $ownership->ownership_percentage,
                        2,
                        '.',
                        ''
                    ), '0'), '.')
                );

                $bills[] = $this->record(
                    ownerAccount: $account,
                    lines: $perOwnerLines[$index],
                    billDate: $billDate,
                    notes: $shareNote,
                    actor: $actor,
                    buildingId: $building->id,
                );
            }

            return $bills;
        });
    }
}

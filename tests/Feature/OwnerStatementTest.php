<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Unit;
use App\Services\Reports\OwnerReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The statement an owner is handed when they come to collect their money.
 *
 * It has to answer four questions in one view: what rent came in, what was
 * spent, what the organisation kept in fees and VAT, and what is left for
 * them. Everything is derived from the owner ledger, so the statement can
 * never disagree with the accounts.
 */
class OwnerStatementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An owner with two tenants: one paying, one who has paid nothing.
     *
     * @return array<string, mixed>
     */
    private function createOwnerWithTwoTenants(): array
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Statement Owner',
        ]);

        $building = Building::create(['name' => 'Statement Court']);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $paying = Party::create([
            'type' => 'person',
            'name' => 'Paying Tenant',
        ]);

        $silent = Party::create([
            'type' => 'person',
            'name' => 'Silent Tenant',
        ]);

        $unitOne = Unit::create([
            'building_id' => $building->id,
            'name' => 'Suite 1',
        ]);

        $unitTwo = Unit::create([
            'building_id' => $building->id,
            'name' => 'Suite 2',
        ]);

        $leaseTerms = [
            'start_date' => '2026-01-01',
            'status' => 'active',
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'management_fee_type' => 'percentage',
            'management_fee_value' => 12,
        ];

        $payingLease = Lease::create($leaseTerms + [
            'unit_id' => $unitOne->id,
            'tenant_id' => $paying->id,
            'rent_amount' => 290000,
        ]);

        $silentLease = Lease::create($leaseTerms + [
            'unit_id' => $unitTwo->id,
            'tenant_id' => $silent->id,
            'rent_amount' => 180000,
        ]);

        $account = OwnerAccount::firstOrCreate([
            'party_id' => $owner->id,
        ]);

        return compact(
            'owner',
            'account',
            'payingLease',
            'silentLease',
            'building'
        );
    }

    private function ledger(
        OwnerAccount $account,
        string $direction,
        string $category,
        int $amount,
        string $date,
        ?int $leaseId = null
    ): void {
        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => $direction,
            'category' => $category,
            'amount' => $amount,
            'transaction_date' => $date,
            'lease_id' => $leaseId,
        ]);
    }

    /**
     * Three months of rent at 290,000, a 12% fee and 18% VAT on that fee.
     *
     *   870,000 rent
     *  - 104,400 fee
     *  -  18,792 VAT on the fee
     *  =  746,808 to the owner
     */
    public function test_statement_shows_rent_fee_vat_and_the_total_due(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $this->ledger($context['account'], 'credit', 'rent_entitlement', 870000, '2026-03-31', $context['payingLease']->id);
        $this->ledger($context['account'], 'debit', 'management_fee', 104400, '2026-03-31', $context['payingLease']->id);
        $this->ledger($context['account'], 'debit', 'management_fee_vat', 18792, '2026-03-31', $context['payingLease']->id);

        $report = app(OwnerReportService::class)
            ->generate($context['owner']);

        $summary = $report['summary'];

        $this->assertSame(870000, $summary['rent_entitlement']);
        $this->assertSame(104400, $summary['management_fees']);

        /*
         * VAT on the fee must be its own line. It is a real deduction from
         * the owner and they are entitled to see it separately from the
         * fee it is charged on.
         */
        $this->assertSame(18792, $summary['management_fee_vat']);

        $this->assertSame(746808, $summary['closing_balance']);
    }

    public function test_statement_subtracts_expenses_from_what_the_owner_receives(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $this->ledger($context['account'], 'credit', 'rent_entitlement', 800000, '2026-03-31', $context['payingLease']->id);
        $this->ledger($context['account'], 'debit', 'management_fee', 120000, '2026-03-31', $context['payingLease']->id);
        $this->ledger($context['account'], 'debit', 'management_fee_vat', 21600, '2026-03-31', $context['payingLease']->id);
        $this->ledger($context['account'], 'debit', 'expense', 178100, '2026-02-10');
        $this->ledger($context['account'], 'debit', 'expense', 47680, '2026-02-20');

        $report = app(OwnerReportService::class)
            ->generate($context['owner']);

        $this->assertSame(225780, $report['summary']['expenses']);

        $this->assertSame(
            800000 - 120000 - 21600 - 225780,
            $report['summary']['closing_balance']
        );
    }

    /**
     * A tenant who paid nothing produces no ledger row, so listing only
     * transactions would make them disappear from the statement. The owner
     * needs to see that the unit is let and brought in nothing.
     */
    public function test_statement_lists_every_tenant_including_those_who_paid_nothing(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $this->ledger($context['account'], 'credit', 'rent_entitlement', 870000, '2026-03-31', $context['payingLease']->id);

        $report = app(OwnerReportService::class)
            ->generate($context['owner']);

        $tenants = collect($report['tenants'])->keyBy('tenant');

        $this->assertCount(2, $tenants);

        $this->assertSame(290000, $tenants['Paying Tenant']['monthly_rent']);
        $this->assertSame(870000, $tenants['Paying Tenant']['rent_collected']);
        $this->assertSame('12%', $tenants['Paying Tenant']['management_fee_rate']);

        $this->assertSame(180000, $tenants['Silent Tenant']['monthly_rent']);
        $this->assertSame(0, $tenants['Silent Tenant']['rent_collected']);
    }

    /**
     * The amount is cash actually received, but an owner thinks in months
     * of rent. February's rent paid late in April is still February's
     * rent, so the statement reports the period the money settles, taken
     * from the invoices the payments were allocated to.
     */
    public function test_statement_reports_the_rent_period_the_money_covers(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $january = \App\Models\Invoice::create([
            'lease_id' => $context['payingLease']->id,
            'invoice_number' => 'INV-STMT-JAN',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-01',
            'status' => 'paid',
            'total_amount' => 290000,
            'vat_rate' => 0,
            'net_amount' => 290000,
            'vat_amount' => 0,
        ]);

        $march = \App\Models\Invoice::create([
            'lease_id' => $context['payingLease']->id,
            'invoice_number' => 'INV-STMT-MAR',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'issue_date' => '2026-03-01',
            'due_date' => '2026-03-01',
            'status' => 'paid',
            'total_amount' => 290000,
            'vat_rate' => 0,
            'net_amount' => 290000,
            'vat_amount' => 0,
        ]);

        /*
         * Both settled in May -- long after the rent they pay for.
         */
        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 290000,
            'transaction_date' => '2026-05-04',
            'lease_id' => $context['payingLease']->id,
            'invoice_id' => $january->id,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 290000,
            'transaction_date' => '2026-05-04',
            'lease_id' => $context['payingLease']->id,
            'invoice_id' => $march->id,
        ]);

        $report = app(OwnerReportService::class)
            ->generate($context['owner'], '2026-05-01', '2026-05-31');

        $paying = collect($report['tenants'])
            ->where('tenant', 'Paying Tenant')
            ->values();

        /*
         * Two rows, not one span. February was never paid, so it is
         * simply absent -- a single 01/01 to 31/03 line would imply the
         * owner is being handed three months of rent when only two were
         * collected.
         */
        $this->assertCount(2, $paying);

        $this->assertSame('2026-01-01', $paying[0]['rent_from_date']);
        $this->assertSame('2026-01-31', $paying[0]['rent_to_date']);
        $this->assertSame(290000, $paying[0]['rent_collected']);

        $this->assertSame('2026-03-01', $paying[1]['rent_from_date']);
        $this->assertSame('2026-03-31', $paying[1]['rent_to_date']);
        $this->assertSame(290000, $paying[1]['rent_collected']);

        $this->assertSame(
            580000,
            collect($paying)->sum('rent_collected')
        );
    }

    /**
     * Rent unpaid at the time of one statement is not lost: it appears on
     * the next one, still carrying the period it belongs to rather than
     * the month it happened to arrive.
     */
    public function test_rent_paid_late_appears_on_the_following_statement(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $february = \App\Models\Invoice::create([
            'lease_id' => $context['payingLease']->id,
            'invoice_number' => 'INV-STMT-FEB',
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'issue_date' => '2026-02-01',
            'due_date' => '2026-02-01',
            'status' => 'paid',
            'total_amount' => 290000,
            'vat_rate' => 0,
            'net_amount' => 290000,
            'vat_amount' => 0,
        ]);

        /*
         * The owner cashed out in May. February settles in July.
         */
        $this->ledger($context['account'], 'debit', 'payout', 400000, '2026-05-10');

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 290000,
            'transaction_date' => '2026-07-03',
            'lease_id' => $context['payingLease']->id,
            'invoice_id' => $february->id,
        ]);

        /*
         * The statement runs from the day after the last cash-out.
         */
        $report = app(OwnerReportService::class)
            ->generate($context['owner'], '2026-05-11', '2026-07-31');

        $paying = collect($report['tenants'])
            ->where('tenant', 'Paying Tenant')
            ->values();

        $this->assertCount(1, $paying);
        $this->assertSame('2026-02-01', $paying[0]['rent_from_date']);
        $this->assertSame('2026-02-28', $paying[0]['rent_to_date']);
        $this->assertSame(290000, $paying[0]['rent_collected']);
    }

    /**
     * Whatever the shape of the rows, they must always total the rent
     * figure in the summary. If they ever disagree the owner is looking
     * at two different answers to the same question.
     */
    public function test_tenant_rows_always_total_the_summary_rent(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $invoice = \App\Models\Invoice::create([
            'lease_id' => $context['payingLease']->id,
            'invoice_number' => 'INV-STMT-RECON',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-01',
            'status' => 'paid',
            'total_amount' => 290000,
            'vat_rate' => 0,
            'net_amount' => 290000,
            'vat_amount' => 0,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 290000,
            'transaction_date' => '2026-02-04',
            'lease_id' => $context['payingLease']->id,
            'invoice_id' => $invoice->id,
        ]);

        /*
         * Entitlement with no Invoice behind it, which cannot be tied to
         * a rent period.
         */
        $this->ledger($context['account'], 'credit', 'rent_entitlement', 45000, '2026-02-06', $context['payingLease']->id);

        $report = app(OwnerReportService::class)
            ->generate($context['owner']);

        $this->assertSame(
            $report['summary']['rent_entitlement'],
            collect($report['tenants'])->sum('rent_collected')
        );

        $this->assertSame(335000, $report['summary']['rent_entitlement']);
    }

    /**
     * The console pre-fills the period from the day after the last payout,
     * because "since I last collected" is the period an owner asks about.
     */
    public function test_statement_reports_the_last_payout_date(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $this->assertNull(
            app(OwnerReportService::class)
                ->generate($context['owner'])['last_payout_date']
        );

        $this->ledger($context['account'], 'debit', 'payout', 500000, '2026-02-15');
        $this->ledger($context['account'], 'debit', 'payout', 300000, '2026-06-20');

        $this->assertSame(
            '2026-06-20',
            app(OwnerReportService::class)
                ->generate($context['owner'])['last_payout_date']
        );
    }

    /**
     * Everything before the period start is carried in as one opening
     * figure, so a statement covering the months since the last payout
     * still reconciles to the true account balance.
     */
    public function test_period_carries_the_earlier_balance_forward(): void
    {
        $context = $this->createOwnerWithTwoTenants();

        $this->ledger($context['account'], 'credit', 'rent_entitlement', 276211, '2026-01-15', $context['payingLease']->id);

        $this->ledger($context['account'], 'credit', 'rent_entitlement', 800000, '2026-05-31', $context['payingLease']->id);
        $this->ledger($context['account'], 'debit', 'management_fee', 120000, '2026-05-31', $context['payingLease']->id);

        $report = app(OwnerReportService::class)
            ->generate($context['owner'], '2026-05-01', '2026-05-31');

        $this->assertSame(276211, $report['summary']['opening_balance']);
        $this->assertSame(800000, $report['summary']['rent_entitlement']);
        $this->assertSame(276211 + 800000 - 120000, $report['summary']['closing_balance']);

        /*
         * The tenant section follows the period too: only rent collected
         * inside it counts, not the earlier balance.
         */
        $tenants = collect($report['tenants'])->keyBy('tenant');

        $this->assertSame(800000, $tenants['Paying Tenant']['rent_collected']);
    }
}

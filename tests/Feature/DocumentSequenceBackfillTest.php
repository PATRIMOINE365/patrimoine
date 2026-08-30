<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Unit;
use App\Services\Documents\DocumentNumberService;
use App\Support\DocumentSequenceBackfill;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * V1.0.36: the half of the numbering change that touches real books.
 *
 * An organisation already holding INV-000123 must not be handed
 * INV-2026-000001 next. The number would not collide — the shapes
 * differ — but a reference that has been on a document sent to somebody
 * must never come back in any shape, and a customer whose invoices
 * suddenly count from one again has no way to know which is which.
 *
 * These run the backfill against books that already have numbers in
 * them, which is what the migration will meet on the day it is deployed.
 */
class DocumentSequenceBackfillTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $organisation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::factory()->create();
    }

    /**
     * A lease per organisation, made once and reused, because an invoice
     * cannot exist without one and this test is about the numbers rather
     * than about the letting behind them.
     *
     * @var array<int, int>
     */
    private array $leases = [];

    private function leaseFor(Organisation $organisation): int
    {
        return $this->leases[$organisation->id] ??= OrganisationContext::runAs(
            (int) $organisation->id,
            function () use ($organisation): int {
                $building = Building::create([
                    'name' => 'Numbering Court '.$organisation->id,
                ]);

                $unit = Unit::create([
                    'building_id' => $building->id,
                    'name' => 'Unit 1',
                ]);

                $tenant = Party::create([
                    'type' => 'person',
                    'name' => 'Numbering Tenant '.$organisation->id,
                    'phone' => '0200000030',
                    'email' => 'numbering'.$organisation->id.'@example.test',
                ]);

                return (int) Lease::create([
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => '2026-01-01',
                    'rent_amount' => 1000,
                    'vat_rate' => 0,
                    'status' => 'active',
                ])->id;
            }
        );
    }

    /**
     * An invoice numbered the old way, as the books already hold them.
     */
    private function legacyInvoice(Organisation $organisation, string $number): void
    {
        $lease = $this->leaseFor($organisation);

        OrganisationContext::runAs(
            (int) $organisation->id,
            fn (): Invoice => Invoice::create([
                'lease_id' => $lease,
                'invoice_number' => $number,
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-01',
                'status' => 'issued',
                'total_amount' => 1000,
                'vat_rate' => 0,
                'net_amount' => 1000,
                'vat_amount' => 0,
            ])
        );
    }

    private function nextNumber(Organisation $organisation, string $series): string
    {
        return OrganisationContext::runAs(
            (int) $organisation->id,
            fn (): string => app(DocumentNumberService::class)->next($series)
        );
    }

    public function test_numbering_continues_from_where_the_books_had_reached(): void
    {
        $this->legacyInvoice($this->organisation, 'INV-000001');
        $this->legacyInvoice($this->organisation, 'INV-000122');
        $this->legacyInvoice($this->organisation, 'INV-000123');

        DocumentSequenceBackfill::run();

        $this->assertSame(
            sprintf('INV-%04d-000124', now()->year),
            $this->nextNumber($this->organisation, 'INV'),
            'the count restarted and a used number could come back'
        );
    }

    /**
     * The old code found the highest number by sorting it as text, so a
     * number of a different width sorted wrongly. The backfill reads the
     * digits instead.
     */
    public function test_the_highest_number_is_read_as_a_number_not_as_text(): void
    {
        $this->legacyInvoice($this->organisation, 'INV-000999');
        $this->legacyInvoice($this->organisation, 'INV-0001000');

        DocumentSequenceBackfill::run();

        $this->assertSame(
            sprintf('INV-%04d-001001', now()->year),
            $this->nextNumber($this->organisation, 'INV')
        );
    }

    public function test_each_organisation_is_seeded_from_its_own_books(): void
    {
        $other = Organisation::factory()->create();

        $this->legacyInvoice($this->organisation, 'INV-000400');
        $this->legacyInvoice($other, 'INV-000007');

        DocumentSequenceBackfill::run();

        $this->assertSame(
            sprintf('INV-%04d-000401', now()->year),
            $this->nextNumber($this->organisation, 'INV')
        );

        $this->assertSame(
            sprintf('INV-%04d-000008', now()->year),
            $this->nextNumber($other, 'INV'),
            'one organisation was seeded from another one\'s numbering'
        );
    }

    /**
     * INV, EXP and SDD all live in the invoices table. Each must be
     * seeded from its own prefix and not from whatever happens to be the
     * highest row.
     */
    public function test_series_sharing_a_table_are_seeded_separately(): void
    {
        $this->legacyInvoice($this->organisation, 'INV-000300');
        $this->legacyInvoice($this->organisation, 'EXP-000005');

        DocumentSequenceBackfill::run();

        $this->assertSame(
            sprintf('EXP-%04d-000006', now()->year),
            $this->nextNumber($this->organisation, 'EXP')
        );

        $this->assertSame(
            sprintf('SDD-%04d-000001', now()->year),
            $this->nextNumber($this->organisation, 'SDD'),
            'a series with no history did not start at one'
        );
    }

    public function test_an_organisation_with_no_history_starts_at_one(): void
    {
        DocumentSequenceBackfill::run();

        $this->assertSame(
            sprintf('OEB-%04d-000001', now()->year),
            $this->nextNumber($this->organisation, 'OEB')
        );
    }

    /**
     * The migration must survive being run twice — and must never lower a
     * counter that has already moved past its seed.
     */
    public function test_running_the_backfill_again_cannot_lower_a_counter(): void
    {
        $this->legacyInvoice($this->organisation, 'INV-000010');

        DocumentSequenceBackfill::run();

        $this->nextNumber($this->organisation, 'INV');
        $this->nextNumber($this->organisation, 'INV');

        DocumentSequenceBackfill::run();

        $this->assertSame(
            sprintf('INV-%04d-000013', now()->year),
            $this->nextNumber($this->organisation, 'INV'),
            'a second run handed back numbers that had already been issued'
        );
    }

    /**
     * The journal's counter is the next number, not the highest issued: a
     * posting that failed may legitimately have consumed one. It is
     * copied, not derived.
     */
    public function test_the_journals_own_counter_is_carried_across_not_recalculated(): void
    {
        DB::table('journal_sequences')->insert([
            'organisation_id' => $this->organisation->id,
            'year' => 2026,
            'next_number' => 77,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DocumentSequenceBackfill::run();

        $this->assertSame(
            'JRN-2026-000077',
            OrganisationContext::runAs(
                (int) $this->organisation->id,
                fn (): string => app(DocumentNumberService::class)->next('JRN', 2026)
            )
        );
    }
}

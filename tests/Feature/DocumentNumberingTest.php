<?php

namespace Tests\Feature;

use App\Models\DocumentSequence;
use App\Models\Organisation;
use App\Services\Documents\DocumentNumberService;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * V1.0.36: one counter behind every document number.
 *
 * Nine of the ten series used to find their next number by reading the
 * highest one already issued. These assertions hold the three things that
 * was wrong about, and the third is the one that matters most: an
 * accounting reference that has been on a document sent to somebody must
 * never come back, whatever happens to that document afterwards.
 */
class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $organisation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::factory()->create();
    }

    private function numbers(): DocumentNumberService
    {
        return app(DocumentNumberService::class);
    }

    private function inOrganisation(Organisation $organisation, callable $work): mixed
    {
        return OrganisationContext::runAs((int) $organisation->id, $work);
    }

    public function test_every_series_is_numbered_in_the_same_shape(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            foreach (array_keys(DocumentNumberService::SERIES) as $series) {
                $this->assertMatchesRegularExpression(
                    '/^'.$series.'-\d{4}-\d{6}$/',
                    $this->numbers()->next($series),
                    $series.' does not carry its year'
                );
            }
        });
    }

    public function test_numbers_advance_one_at_a_time(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $year = now()->year;

            $this->assertSame(
                sprintf('INV-%04d-000001', $year),
                $this->numbers()->next('INV')
            );

            $this->assertSame(
                sprintf('INV-%04d-000002', $year),
                $this->numbers()->next('INV')
            );

            $this->assertSame(
                sprintf('INV-%04d-000003', $year),
                $this->numbers()->next('INV')
            );
        });
    }

    public function test_one_series_does_not_advance_another(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $year = now()->year;

            $this->numbers()->next('INV');
            $this->numbers()->next('INV');

            $this->assertSame(
                sprintf('EXP-%04d-000001', $year),
                $this->numbers()->next('EXP'),
                'EXP borrowed the invoice counter'
            );
        });
    }

    /**
     * The bug worth keeping a test for.
     *
     * The number used to be the highest one found among the documents, so
     * deleting the newest handed its number straight back to the next one
     * written. Two different documents, the same reference, and nothing
     * anywhere complaining.
     */
    public function test_a_number_is_never_reissued_after_its_document_is_gone(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $first = $this->numbers()->next('OEB');
            $second = $this->numbers()->next('OEB');

            /*
             * Nothing was written to owner_expense_bills at all here,
             * which is the point: the counter does not consult them, so
             * whether the documents exist, were never created, or were
             * deleted afterwards cannot change what comes next.
             */
            $third = $this->numbers()->next('OEB');

            $this->assertNotSame($first, $second);
            $this->assertNotSame($second, $third);

            $this->assertSame(
                sprintf('OEB-%04d-000003', now()->year),
                $third
            );
        });
    }

    public function test_each_organisation_runs_its_own_series(): void
    {
        $other = Organisation::factory()->create();

        $year = now()->year;

        $mine = $this->inOrganisation(
            $this->organisation,
            fn (): string => $this->numbers()->next('WDR')
        );

        $theirs = $this->inOrganisation(
            $other,
            fn (): string => $this->numbers()->next('WDR')
        );

        $this->assertSame(sprintf('WDR-%04d-000001', $year), $mine);

        $this->assertSame(
            sprintf('WDR-%04d-000001', $year),
            $theirs,
            'a new organisation was shown how many documents another one has'
        );

        $mineAgain = $this->inOrganisation(
            $this->organisation,
            fn (): string => $this->numbers()->next('WDR')
        );

        $this->assertSame(sprintf('WDR-%04d-000002', $year), $mineAgain);
    }

    public function test_the_count_restarts_with_the_year(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $this->numbers()->next('ADV', 2026);
            $this->numbers()->next('ADV', 2026);

            $this->assertSame(
                'ADV-2027-000001',
                $this->numbers()->next('ADV', 2027)
            );

            $this->assertSame(
                'ADV-2026-000003',
                $this->numbers()->next('ADV', 2026),
                'a late 2026 document did not continue the 2026 count'
            );
        });
    }

    public function test_one_counter_row_holds_each_series_and_year(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $this->numbers()->next('TRF', 2026);
            $this->numbers()->next('TRF', 2026);
            $this->numbers()->next('TRF', 2027);

            $this->assertSame(
                2,
                DocumentSequence::query()->where('series', 'TRF')->count()
            );

            $this->assertSame(
                3,
                (int) DocumentSequence::query()
                    ->where('series', 'TRF')
                    ->where('year', 2026)
                    ->value('next_number')
            );
        });
    }

    /**
     * A series is a fixed list, not a free string, so a typo is a failure
     * here rather than a new series nobody knows exists.
     */
    public function test_an_unknown_series_is_refused(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $this->expectException(InvalidArgumentException::class);

            $this->numbers()->next('NOPE');
        });
    }

    /**
     * The journal invented this mechanism and now shares it. Its numbers
     * must be exactly what they always were.
     */
    public function test_the_journal_still_numbers_itself_the_way_it_always_did(): void
    {
        $this->inOrganisation($this->organisation, function (): void {
            $this->assertSame(
                'JRN-2026-000001',
                app(\App\Services\Accounting\JournalNumberService::class)->next(2026)
            );
        });
    }
}

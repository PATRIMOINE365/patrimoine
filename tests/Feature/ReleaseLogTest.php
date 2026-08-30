<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.31 split the update log in two.
 *
 * Patrimoine is one running service: nobody is on an old version and
 * nobody can go back to one, so a release-by-release history is an
 * archive of things that are all simply true now, and thirty of them
 * buries the two or three a customer wants. Customers read a log written
 * in fives; support reads the whole thing in the console.
 */
class ReleaseLogTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /*
    |--------------------------------------------------------------------------
    | What a customer reads
    |--------------------------------------------------------------------------
    */

    public function test_the_customer_log_is_written_in_fives(): void
    {
        $response = $this
            ->getJson('/api/release-log')
            ->assertOk();

        $versions = array_column(
            $response->json('entries'),
            'version'
        );

        $this->assertNotEmpty($versions);

        /*
         * Every entry below the newest ends on a multiple of five. The
         * newest is the one still being filled and carries whatever is
         * running, which is asserted separately.
         */
        foreach (array_slice($versions, 1) as $version) {
            $patch = (int) substr(
                $version,
                strrpos($version, '.') + 1
            );

            $this->assertSame(
                0,
                $patch % 5,
                $version.' is not the end of a block of five.'
            );
        }

        $this->assertSame(
            $versions,
            array_values(array_unique($versions)),
            'A version is listed twice.'
        );
    }

    public function test_the_newest_entry_carries_the_running_version(): void
    {
        $current = (string) config('patrimoine.release');

        $entries = $this
            ->getJson('/api/release-log')
            ->assertOk()
            ->json('entries');

        $this->assertSame(
            $current,
            $entries[0]['version'],
            'The top of the log must be the version the customer is on.'
        );
    }

    public function test_nothing_unreleased_is_listed(): void
    {
        $current = (string) config('patrimoine.release');

        foreach (
            $this->getJson('/api/release-log')->json('entries')
            as $entry
        ) {
            $this->assertTrue(
                version_compare($entry['version'], $current, '<='),
                $entry['version'].' has not shipped.'
            );
        }
    }

    public function test_each_entry_is_a_couple_of_sentences_not_a_list(): void
    {
        foreach (
            $this->getJson('/api/release-log')->json('entries')
            as $entry
        ) {
            $this->assertArrayHasKey('summary', $entry);

            $this->assertArrayNotHasKey(
                'changes',
                $entry,
                'The customer log carries prose, not the detailed list.'
            );

            $this->assertNotSame('', trim($entry['summary']));

            $sentences = preg_match_all(
                '/[.!?](\s|$)/u',
                $entry['summary']
            );

            $this->assertLessThanOrEqual(
                3,
                $sentences,
                'A block summary is a couple of sentences: '.$entry['summary']
            );
        }
    }

    public function test_the_summaries_exist_in_both_languages(): void
    {
        $english = trans('release_summaries.entries', [], 'en');
        $french = trans('release_summaries.entries', [], 'fr');

        $this->assertSame(
            array_column($english, 'through'),
            array_column($french, 'through'),
            'The two languages must cover the same blocks.'
        );

        foreach ([$english, $french] as $entries) {
            foreach ($entries as $entry) {
                $this->assertNotSame('', trim($entry['summary'] ?? ''));

                $this->assertNotSame('', trim($entry['date'] ?? ''));
            }
        }
    }

    public function test_a_block_is_open_for_the_versions_being_written(): void
    {
        $current = (string) config('patrimoine.release');

        $blocks = array_column(
            trans('release_summaries.entries', [], 'en'),
            'through'
        );

        $ahead = array_filter(
            $blocks,
            fn (string $through): bool => version_compare($through, $current, '>')
        );

        $this->assertNotEmpty(
            $ahead,
            'Without a block ahead of the running version, the next '
            .'releases would have nowhere to be described.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | What support reads
    |--------------------------------------------------------------------------
    */

    public function test_the_full_history_stays_in_the_console(): void
    {
        $detailed = trans('releases.entries', [], 'en');

        $this->assertGreaterThan(
            25,
            count($detailed),
            'The release-by-release history should still be complete.'
        );

        foreach ($detailed as $entry) {
            $this->assertArrayHasKey('changes', $entry);

            $this->assertArrayHasKey('title', $entry);
        }
    }

    public function test_a_customer_cannot_read_the_console_history(): void
    {
        $this
            ->getJson('/api/admin/release-log')
            ->assertForbidden();
    }

    /**
     * On the day a block's own version ships, the next block waits.
     *
     * The entry still being filled borrows the running version, because
     * that is the number a customer can check against their screen. But
     * when the version that COMPLETES a block ships, the block owns that
     * number itself — and the next block, opened so later work has
     * somewhere to go, would borrow the same one and appear beside it as a
     * second entry reading the same version.
     */
    public function test_the_open_block_waits_while_a_finished_block_owns_the_running_version(): void
    {
        $entries = $this->getJson('/api/release-log')->json('entries');

        $versions = array_column($entries, 'version');

        $this->assertSame(
            $versions,
            array_values(array_unique($versions)),
            'Two entries cannot both claim the same version.'
        );

        $current = (string) config('patrimoine.release');

        $blocks = array_column(
            trans('release_summaries.entries', [], 'en'),
            'through'
        );

        if (in_array($current, $blocks, true)) {
            $this->assertSame(
                $current,
                $versions[0] ?? null,
                'The newest visible entry is the block that owns the running version.'
            );

            $this->assertCount(
                1,
                array_filter($versions, fn (string $v): bool => $v === $current),
                'The open block must not borrow a version another block owns.'
            );
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\User;
use App\Support\OrganisationContext;
use App\Support\PageSize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.32 gave every list in Patrimoine one pagination control: a choice of
 * 25, 50 or 100 rows, numbered pages, and the page being read marked as
 * such.
 *
 * These assertions hold the two halves of that together. The server must
 * honour the sizes the browser offers and refuse to be talked into
 * anything else, and every list on screen must have somewhere to draw the
 * control — a list that quietly kept its old Previous/Next, or none at
 * all, is exactly what this release set out to remove.
 */
class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $organisation;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::factory()->create();

        $this->administrator = OrganisationContext::runAs(
            (int) $this->organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->organisation)
                ->create(['role' => 'administrator'])
        );
    }

    private function request(array $query): Request
    {
        return Request::create('/', 'GET', $query);
    }

    /**
     * Put enough parties on the books to need more than one page.
     */
    private function createParties(int $count): void
    {
        OrganisationContext::runAs(
            (int) $this->organisation->id,
            function () use ($count): void {
                for ($index = 1; $index <= $count; $index++) {
                    $party = Party::create([
                        'type' => 'person',
                        'name' => 'Paged Party '.$index,
                        'phone' => '+2332000'.str_pad(
                            (string) $index,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ),
                        'phone_country' => 'GH',
                        'email' => 'paged-'.$index.'@example.test',
                    ]);

                    PartyRole::create([
                        'party_id' => $party->id,
                        'role' => 'tenant',
                    ]);
                }
            }
        );
    }

    /*
    |----------------------------------------------------------------------
    | The sizes on offer
    |----------------------------------------------------------------------
    */

    public function test_the_offered_sizes_are_the_ones_the_browser_shows(): void
    {
        $this->assertSame(
            [25, 50, 100],
            PageSize::OPTIONS,
            'resources/js/pagination.js offers exactly these three.'
        );

        $this->assertSame(25, PageSize::DEFAULT);

        $this->assertContains(PageSize::DEFAULT, PageSize::OPTIONS);
    }

    public function test_every_offered_size_is_accepted(): void
    {
        foreach (PageSize::OPTIONS as $size) {
            $this->assertSame(
                $size,
                PageSize::fromRequest(
                    $this->request(['per_page' => $size])
                )
            );
        }
    }

    public function test_a_size_nobody_offers_quietly_becomes_the_default(): void
    {
        /*
         * A page size is a preference, not an instruction that can fail.
         * A hand-edited query string gets the default rather than a 422.
         */
        foreach ([0, 1, 7, 26, 99, 101, 5000, -25, 'many', ''] as $requested) {
            $this->assertSame(
                PageSize::DEFAULT,
                PageSize::fromRequest(
                    $this->request(['per_page' => $requested])
                ),
                'per_page='.var_export($requested, true).' should fall back.'
            );
        }
    }

    public function test_asking_for_nothing_gets_the_default(): void
    {
        $this->assertSame(
            PageSize::DEFAULT,
            PageSize::fromRequest($this->request([]))
        );
    }

    /*
    |----------------------------------------------------------------------
    | The server honours the choice
    |----------------------------------------------------------------------
    */

    public function test_a_customer_list_returns_the_page_size_it_was_asked_for(): void
    {
        $this->createParties(30);

        Sanctum::actingAs($this->administrator);

        foreach (PageSize::OPTIONS as $size) {
            $response = $this->getJson('/api/parties?per_page='.$size);

            $response->assertOk();

            $this->assertSame(
                $size,
                (int) $response->json('per_page'),
                'The parties list should page in '.$size.'s when asked.'
            );
        }
    }

    public function test_the_smallest_size_really_does_split_the_list(): void
    {
        $this->createParties(30);

        Sanctum::actingAs($this->administrator);

        $response = $this->getJson('/api/parties?per_page=25');

        $response->assertOk();

        $total = (int) $response->json('total');

        $this->assertGreaterThanOrEqual(30, $total);

        $this->assertCount(25, $response->json('data'));

        $this->assertSame(
            (int) ceil($total / 25),
            (int) $response->json('last_page')
        );

        /*
         * The summary line the control draws is built from these, so they
         * have to be there.
         */
        $this->assertSame(1, (int) $response->json('from'));
        $this->assertSame(25, (int) $response->json('to'));
    }

    /*
    |----------------------------------------------------------------------
    | Somewhere to draw it
    |----------------------------------------------------------------------
    */

    /**
     * Every list on screen, and the element its control is drawn into.
     *
     * @return array<string, string>
     */
    private function lists(): array
    {
        return [
            'app/parties.blade.php' => 'parties-pagination',
            'app/properties.blade.php' => 'properties-pagination',
            'app/tenants.blade.php' => 'tenant-pagination',
            'app/leases.blade.php' => 'leases-pagination',
            'app/owners.blade.php' => 'owners-list-pagination',
            'app/panels/users.blade.php' => 'users-pagination',
            'app/activity-log.blade.php' => 'activity-log-pagination',
            'app/financial-journal.blade.php' => 'financial-journal-pagination',
            'app/help.blade.php' => 'help-errors-pagination',
            'errors-reference.blade.php' => 'error-pagination',
        ];
    }

    public function test_every_list_has_somewhere_to_draw_the_control(): void
    {
        foreach ($this->lists() as $view => $container) {
            $this->assertStringContainsString(
                'id="'.$container.'"',
                file_get_contents(resource_path('views/'.$view)),
                $view.' should carry the '.$container.' container.'
            );
        }
    }

    public function test_the_console_lists_have_somewhere_too(): void
    {
        $console = file_get_contents(
            resource_path('views/app/admin.blade.php')
        );

        foreach ([
            'admin-pagination',
            'admin-licenses-pagination',
            'admin-activity-pagination',
            'admin-staff-pagination',
            'admin-records-pagination',
            'admin-releases-pagination',
        ] as $container) {
            $this->assertStringContainsString(
                'id="'.$container.'"',
                $console,
                'The console should carry the '.$container.' container.'
            );
        }
    }

    public function test_no_list_still_draws_its_own_previous_and_next(): void
    {
        /*
         * The twelve hand-rolled renderers this release replaced all built
         * their own two buttons. Any that came back would be a list the
         * shared control had quietly stopped reaching.
         */
        foreach ([
            'parties',
            'properties',
            'tenants',
            'leases',
            'owners',
            'users',
            'activity-log',
            'financial-journal',
        ] as $module) {
            $source = file_get_contents(
                resource_path('js/'.$module.'.js')
            );

            $this->assertStringContainsString(
                "renderPagination(",
                $source,
                $module.'.js should delegate to the shared control.'
            );

            $this->assertStringNotContainsString(
                'pm-button-secondary\n                        disabled:cursor-not-allowed',
                $source,
                $module.'.js should no longer build its own buttons.'
            );
        }
    }

    /*
    |----------------------------------------------------------------------
    | The words
    |----------------------------------------------------------------------
    */

    /**
     * @return array<int, string>
     */
    private function keys(): array
    {
        return [
            'summary',
            'empty',
            'rows_per_page',
            'navigation',
            'previous',
            'next',
            'go_to_page',
            'current_page',
        ];
    }

    public function test_the_control_speaks_both_languages(): void
    {
        foreach (['en', 'fr'] as $language) {
            foreach ($this->keys() as $key) {
                $sentence = __('ui.pagination.'.$key, [], $language);

                $this->assertIsString($sentence);

                $this->assertNotSame(
                    'ui.pagination.'.$key,
                    $sentence,
                    $language.' is missing pagination.'.$key
                );

                $this->assertNotSame('', trim($sentence));
            }
        }
    }

    public function test_the_summary_and_page_labels_carry_their_placeholders(): void
    {
        foreach (['en', 'fr'] as $language) {
            $summary = __('ui.pagination.summary', [], $language);

            foreach ([':from', ':to', ':total'] as $placeholder) {
                $this->assertStringContainsString(
                    $placeholder,
                    $summary,
                    $language.' pagination.summary needs '.$placeholder
                );
            }

            foreach (['go_to_page', 'current_page'] as $key) {
                $this->assertStringContainsString(
                    ':page',
                    __('ui.pagination.'.$key, [], $language),
                    $language.' pagination.'.$key.' needs :page'
                );
            }
        }
    }

    public function test_the_browser_catalogue_carries_the_same_keys(): void
    {
        $catalogue = file_get_contents(
            resource_path('js/translations.js')
        );

        foreach ($this->keys() as $key) {
            $this->assertSame(
                2,
                substr_count($catalogue, "'pagination.".$key."':"),
                'pagination.'.$key.' should appear once in each language.'
            );
        }
    }

    public function test_the_public_error_reference_can_speak_without_the_bundle(): void
    {
        /*
         * The reference must render with no database and no bundle, so its
         * control is drawn by an inline script that is told its words by
         * the server rather than by translations.js.
         */
        $view = file_get_contents(
            resource_path('views/errors-reference.blade.php')
        );

        foreach ([
            'data-summary',
            'data-rows-per-page',
            'data-navigation',
            'data-previous',
            'data-next',
            'data-go-to-page',
            'data-current-page',
        ] as $attribute) {
            $this->assertStringContainsString(
                $attribute.'=',
                $view,
                'The reference should hand '.$attribute.' to its script.'
            );
        }

        $this->assertStringContainsString(
            'pm-pagination-page',
            $view,
            'The reference should draw the same control as everywhere else.'
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\User;
use App\Support\Guide;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.33 replaced the prose guide with a manual: every task in Patrimoine,
 * step by step, illustrated by screenshots taken from the running
 * application.
 *
 * A manual has three ways of going quietly wrong, and this holds all three
 * shut. It can drift between languages, so that a French reader is missing
 * a step an English one has. It can reference a picture that does not
 * exist, which shows a customer a broken image. And it can accumulate
 * pictures nothing references, which is how a folder of stale screenshots
 * outlives the screens it photographed.
 */
class GuideTest extends TestCase
{
    use RefreshDatabase;

    /*
    |----------------------------------------------------------------------
    | The two languages say the same thing
    |----------------------------------------------------------------------
    */

    public function test_french_has_every_category_and_task_english_has(): void
    {
        $en = Guide::for('en')['categories'];
        $fr = Guide::for('fr')['categories'];

        $this->assertSame(
            array_column($en, 'id'),
            array_column($fr, 'id'),
            'The categories, and their order, must match.'
        );

        foreach ($en as $index => $category) {
            $this->assertSame(
                array_column($category['tasks'], 'id'),
                array_column($fr[$index]['tasks'], 'id'),
                'Tasks in '.$category['id'].' must match.'
            );
        }
    }

    public function test_every_task_has_the_same_steps_in_both_languages(): void
    {
        $en = Guide::for('en')['categories'];
        $fr = Guide::for('fr')['categories'];

        foreach ($en as $c => $category) {
            foreach ($category['tasks'] as $t => $task) {
                $other = $fr[$c]['tasks'][$t];

                $this->assertSame(
                    count($task['steps']),
                    count($other['steps']),
                    $category['id'].'/'.$task['id'].' must have the same number of steps.'
                );

                foreach ($task['steps'] as $s => $step) {
                    $this->assertSame(
                        $step['shot'],
                        $other['steps'][$s]['shot'],
                        $category['id'].'/'.$task['id'].' step '.($s + 1)
                            .' must illustrate the same screen in both languages.'
                    );
                }
            }
        }
    }

    public function test_nothing_in_the_manual_is_left_empty(): void
    {
        foreach (Guide::LOCALES as $locale) {
            foreach (Guide::for($locale)['categories'] as $category) {
                $this->assertNotSame('', trim($category['title']));
                $this->assertNotSame('', trim($category['summary']));

                $this->assertNotEmpty(
                    $category['tasks'],
                    $category['id'].' has no tasks.'
                );

                foreach ($category['tasks'] as $task) {
                    $this->assertNotSame('', trim($task['title']));
                    $this->assertNotSame('', trim($task['intro']));

                    $this->assertNotEmpty(
                        $task['steps'],
                        $task['id'].' has no steps.'
                    );

                    foreach ($task['steps'] as $step) {
                        $this->assertNotSame(
                            '',
                            trim($step['text']),
                            $task['id'].' has an empty step.'
                        );
                    }
                }
            }
        }
    }

    /*
    |----------------------------------------------------------------------
    | Every picture exists, and every picture is used
    |----------------------------------------------------------------------
    */

    public function test_every_screenshot_the_manual_asks_for_exists(): void
    {
        $shots = Guide::shots();

        $this->assertNotEmpty($shots, 'The manual should be illustrated.');

        foreach (Guide::LOCALES as $locale) {
            foreach ($shots as $shot) {
                $this->assertFileExists(
                    public_path('guide/'.$locale.'/'.$shot.'.webp'),
                    $locale.'/'.$shot.'.webp is referenced but missing.'
                );
            }
        }
    }

    public function test_no_screenshot_outlives_the_step_it_illustrated(): void
    {
        $wanted = Guide::shots();

        foreach (Guide::LOCALES as $locale) {
            $files = glob(public_path('guide/'.$locale.'/*.webp')) ?: [];

            $held = array_map(
                fn (string $path): string => basename($path, '.webp'),
                $files
            );

            $orphans = array_diff($held, $wanted);

            $this->assertSame(
                [],
                array_values($orphans),
                'These '.$locale.' screenshots are no longer referenced: '
                    .implode(', ', $orphans)
            );
        }
    }

    public function test_every_screenshot_can_be_taken_again(): void
    {
        /*
         * A picture that cannot be re-captured is a picture that will be
         * wrong after the next redesign and stay wrong, because nobody will
         * know how it was taken.
         */
        $manifest = json_decode(
            (string) file_get_contents(base_path('scripts/guide-shots.json')),
            true
        );

        $this->assertIsArray($manifest);

        $captured = [];

        foreach ($manifest as $entry) {
            if (isset($entry['id']) && is_string($entry['id'])) {
                $captured[] = $entry['id'];
            }

            foreach ($entry['do'] ?? [] as $step) {
                if (isset($step['capture'])) {
                    $captured[] = $step['capture'];
                }
            }
        }

        foreach (Guide::shots() as $shot) {
            $this->assertContains(
                $shot,
                $captured,
                $shot.' has no entry in scripts/guide-shots.json, so it '
                    .'cannot be captured again.'
            );
        }
    }

    /*
    |----------------------------------------------------------------------
    | Reading it
    |----------------------------------------------------------------------
    */

    private function userWithRole(string $role): User
    {
        $organisation = Organisation::factory()->create();

        return OrganisationContext::runAs(
            (int) $organisation->id,
            fn (): User => User::factory()
                ->forOrganisation($organisation)
                ->create(['role' => $role])
        );
    }

    public function test_the_manual_is_served_to_every_role(): void
    {
        /*
         * Including a Viewer. Somebody who may not record a payment still
         * needs to read how one is recorded.
         */
        foreach (['administrator', 'property_manager', 'viewer'] as $role) {
            Sanctum::actingAs($this->userWithRole($role));

            $response = $this->getJson('/api/guide');

            $response->assertOk();

            $this->assertNotEmpty(
                $response->json('categories'),
                $role.' should be able to read the guide.'
            );
        }
    }

    public function test_a_stranger_is_not_served_the_manual(): void
    {
        $this->getJson('/api/guide')->assertUnauthorized();
    }

    public function test_the_response_carries_everything_the_browser_draws(): void
    {
        Sanctum::actingAs($this->userWithRole('administrator'));

        $response = $this->getJson('/api/guide');

        $response->assertOk();

        $this->assertSame('/guide/en', $response->json('shots'));

        $first = $response->json('categories.0');

        $this->assertArrayHasKey('title', $first);
        $this->assertArrayHasKey('summary', $first);
        $this->assertArrayHasKey('tasks', $first);

        $task = $first['tasks'][0];

        $this->assertArrayHasKey('intro', $task);
        $this->assertArrayHasKey('steps', $task);

        $step = $task['steps'][0];

        $this->assertSame(1, $step['number']);
        $this->assertArrayHasKey('text', $step);
        $this->assertArrayHasKey('shot', $step);
    }

    /*
    |----------------------------------------------------------------------
    | It covers the application
    |----------------------------------------------------------------------
    */

    public function test_the_manual_covers_every_part_of_the_application(): void
    {
        $categories = array_column(Guide::for('en')['categories'], 'id');

        foreach ([
            'getting_started',
            'properties',
            'parties',
            'leases',
            'money_in',
            'owners',
            'invoicing',
            'reports',
            'journal',
            'admin',
        ] as $expected) {
            $this->assertContains(
                $expected,
                $categories,
                'The guide should cover '.$expected.'.'
            );
        }

        /*
         * A floor rather than a target. It is here so that a refactor that
         * silently drops half the manual fails instead of shipping.
         */
        $this->assertGreaterThanOrEqual(
            50,
            Guide::taskCount(),
            'The guide should document every task in Patrimoine.'
        );
    }
}

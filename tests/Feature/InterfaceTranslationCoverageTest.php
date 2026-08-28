<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Every string the interface shows must exist in both languages, on both
 * sides.
 *
 * Two real defects motivated this, both found by browsing pre-prod rather
 * than by any test:
 *
 * - `ui.owners.collector_placeholder` existed in no catalogue at all, so
 *   the Cashier field on the owner deposit drawer showed the raw key to
 *   customers;
 * - several shell strings were rendered server-side with no data-i18n
 *   hook, so they never repainted and a French organisation read an
 *   English sidebar whenever the server answered before the organisation
 *   language was known.
 *
 * Neither is visible to a unit test of behaviour, and both are trivially
 * detectable by reading the templates.
 */
class InterfaceTranslationCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every `ui.*` key rendered by a Blade template.
     *
     * @return array<string, string> key => the file that renders it
     */
    private function renderedKeys(): array
    {
        $keys = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all(
                "/__\('ui\.([a-zA-Z_.0-9]+)'\)/",
                $file->getContents(),
                $matches
            );

            foreach ($matches[1] as $key) {
                $keys[$key] ??= $file->getRelativePathname();
            }
        }

        return $keys;
    }

    /**
     * A rendered key that does not exist comes back as its own name, and
     * that is exactly what the customer sees.
     */
    public function test_every_rendered_string_exists_in_both_languages(): void
    {
        $missing = [];

        foreach ($this->renderedKeys() as $key => $file) {
            foreach (['en', 'fr'] as $locale) {
                $translated = trans("ui.{$key}", [], $locale);

                if ($translated === "ui.{$key}") {
                    $missing[] = "{$locale}: ui.{$key} ({$file})";
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Interface strings rendered but never translated:\n"
                .implode("\n", $missing)
        );
    }

    /**
     * Whatever the browser is asked to repaint must exist in its own
     * catalogue, in both halves — translate() answers with the key when
     * it does not, which would print the key on screen.
     */
    public function test_every_browser_hook_exists_in_the_browser_catalogue(): void
    {
        $catalogue = File::get(resource_path('js/translations.js'));

        /*
         * The Help workspace registers its own keys into the shared
         * catalogue at module load, so they are legitimately absent from
         * translations.js.
         */
        $help = File::get(resource_path('js/help.js'));

        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all(
                '/data-i18n(?:-placeholder|-aria-label|-title)?="([a-zA-Z_.0-9]+)"/',
                $file->getContents(),
                $matches
            );

            foreach ($matches[1] as $key) {
                $inCatalogue = substr_count(
                    $catalogue,
                    "'{$key}':"
                );

                if ($inCatalogue >= 2) {
                    continue;
                }

                if (substr_count($help, "'{$key}':") >= 2) {
                    continue;
                }

                $missing[] = "{$key} ({$file->getRelativePathname()}, found {$inCatalogue} of 2)";
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($missing)),
            "Browser hooks with no translation in both languages:\n"
                .implode("\n", array_unique($missing))
        );
    }

    /**
     * The shell is rendered on every page, so an unhooked string there is
     * the one a customer is most likely to read in the wrong language.
     */
    public function test_the_application_shell_repaints_every_string(): void
    {
        $layout = File::get(
            resource_path('views/layouts/app.blade.php')
        );

        $lines = explode("\n", $layout);

        $unhooked = [];

        foreach ($lines as $index => $line) {
            if (! preg_match("/^\s*\{\{ __\('ui\.([a-zA-Z_.0-9]+)'\) \}\}$/", $line, $m)) {
                continue;
            }

            /*
             * Walk back to the opening tag holding this string and make
             * sure it carries a hook the browser can repaint through.
             */
            $tag = '';

            for ($i = $index - 1; $i >= 0 && $i > $index - 25; $i--) {
                $tag = $lines[$i]."\n".$tag;

                if (preg_match('/<[a-zA-Z]/', $lines[$i])) {
                    break;
                }
            }

            if (! str_contains($tag, 'data-i18n')) {
                $unhooked[] = $m[1];
            }
        }

        $this->assertSame(
            [],
            $unhooked,
            "Shell strings that can never be repainted by the browser:\n"
                .implode("\n", $unhooked)
        );
    }
}

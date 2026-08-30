<?php

namespace Tests\Feature;

use ParseError;
use Tests\TestCase;

/**
 * Every language file has to be readable PHP.
 *
 * This exists because it was not. The French release notes for v1.0.37 were
 * written with bare apostrophes inside single-quoted strings — L'application,
 * l'identité, n'a changé — which is a syntax error, and it reached production.
 * Nothing caught it: a language file is only read when something asks for a
 * translation out of it, no test asked for a French release note, and so the
 * file was never opened by anything until a French customer opened the update
 * log and got a 500.
 *
 * Loading the file is the test. It is evaluated rather than required because
 * require answers true the second time it is handed a path, and the framework
 * has already required several of these while booting — so half the files
 * would be checked and the other half would pass on a technicality.
 */
class LanguageFilesParseTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function languageFiles(): array
    {
        return glob(
            lang_path('*/*.php')
        ) ?: [];
    }

    public function test_every_language_file_parses_and_returns_translations(): void
    {
        $files = $this->languageFiles();

        $this->assertGreaterThan(
            20,
            count($files),
            'The language files were not found, so nothing was checked.'
        );

        foreach ($files as $file) {
            $source = file_get_contents($file);

            $this->assertNotFalse(
                $source,
                $file.' could not be read.'
            );

            /*
             * eval() is given an expression, not a file, so the opening tag
             * has to go. What follows it is `return [ ... ];`, which is
             * exactly what eval evaluates.
             */
            $body = preg_replace(
                '/^\s*<\?php/',
                '',
                $source,
                1
            );

            try {
                $contents = eval($body);
            } catch (ParseError $error) {
                $this->fail(
                    $file.' is not valid PHP: '.$error->getMessage()
                );
            }

            $this->assertIsArray(
                $contents,
                $file.' does not return an array of translations.'
            );

            $this->assertNotEmpty(
                $contents,
                $file.' returns nothing.'
            );
        }
    }

    /**
     * A French file that is missing altogether parses perfectly.
     */
    public function test_both_languages_hold_the_same_files(): void
    {
        $names = [];

        foreach ($this->languageFiles() as $file) {
            $names[basename(dirname($file))][] = basename($file);
        }

        $this->assertArrayHasKey('en', $names);
        $this->assertArrayHasKey('fr', $names);

        sort($names['en']);
        sort($names['fr']);

        $this->assertSame(
            $names['en'],
            $names['fr'],
            'English and French do not hold the same set of files.'
        );
    }
}

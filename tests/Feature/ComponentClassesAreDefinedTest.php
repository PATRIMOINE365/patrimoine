<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every `pm-` class the product writes has to be a class the stylesheet
 * draws.
 *
 * This exists because the v1.0.37 redesign replaced the authentication
 * hero's utility classes with ten component classes and then defined none
 * of them. The page still rendered — a light column where a Patrimoine
 * Green band should be — and it reached production, because nothing fails
 * when markup names a class that does not exist.
 *
 * The reverse direction is not checked. A rule with no markup behind it is
 * dead weight; a class with no rule behind it is a broken screen.
 */
class ComponentClassesAreDefinedTest extends TestCase
{
    /**
     * Classes the stylesheet is not expected to define.
     *
     * These are markers: JavaScript finds elements by them, or they exist
     * to be matched by a selector built from another class.
     *
     * @var list<string>
     */
    private const MARKERS = [
        // Drawer state, toggled by core.js and matched as `.pm-drawer.pm-…`.
        'pm-drawer-active',
        'pm-drawer-open',
        'pm-drawer-closing',

        // Ownership-total states, matched as `#ownership-total.pm-…`.
        'pm-ownership-total-valid',
        'pm-ownership-total-incomplete',
        'pm-ownership-total-excess',

        /*
         * Built by concatenation — `pm-flag-${countryCode}` and
         * `pm-admin-plan-${plan}` — so the whole set is generated and the
         * prefix alone never appears in the stylesheet.
         */
        'pm-flag-',
        'pm-admin-plan-',

        /*
         * Page and section markers. They name what a block IS so that
         * JavaScript can find it and so that a reader of the markup can
         * tell one region from another; they have never carried a rule.
         *
         * They are listed rather than pattern-matched on purpose. A marker
         * is cheap and occasionally useful, but a class that looks like a
         * component and draws nothing is how the authentication hero
         * shipped as a blank column — so adding one should be a decision
         * somebody writes down here, not a habit.
         */
        'pm-page',
        'pm-leases-page',
        'pm-reports-page',
        'pm-reports-controls',
        'pm-reports-output',
        'pm-reports-output-shell',
        'pm-properties-page',
        'pm-property-section-add-action',
        'pm-parties-page',
        'pm-accounting-page',
        'pm-wizard-page',
        'pm-auth-login',
        'pm-lease-financial-history-drawer-body',
        'pm-lease-financial-history-exports',
        'pm-admin-card-note',
        'pm-guide-figure',
        'pm-guide-shot',
    ];

    public function test_every_component_class_used_in_markup_has_a_rule(): void
    {
        $defined = $this->definedClasses();
        $missing = [];

        foreach ($this->usedClasses() as $class => $files) {
            if (in_array($class, self::MARKERS, true)) {
                continue;
            }

            if (! isset($defined[$class])) {
                $missing[$class] = implode(', ', array_slice($files, 0, 3));
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These classes are written but never drawn:\n  "
            .implode(
                "\n  ",
                array_map(
                    static fn ($c, $where) => $c.'  ('.$where.')',
                    array_keys($missing),
                    $missing
                )
            )
        );
    }

    /**
     * Every pm-* class the stylesheets define.
     *
     * @return array<string, true>
     */
    private function definedClasses(): array
    {
        $found = [];

        foreach (['components.css', 'app.css', 'hidden-guards.css', 'flags.css'] as $file) {
            $css = file_get_contents(
                resource_path('css/'.$file)
            );

            /* Comments first: a class named in prose is not a definition. */
            $css = preg_replace('/\/\*[\s\S]*?\*\//', '', (string) $css);

            preg_match_all(
                '/\.(pm-[a-z0-9-]+)/',
                (string) $css,
                $matches
            );

            foreach ($matches[1] as $class) {
                $found[$class] = true;
            }
        }

        return $found;
    }

    /**
     * Every pm-* class the Blade views and the JavaScript write.
     *
     * @return array<string, list<string>>
     */
    private function usedClasses(): array
    {
        $used = [];

        foreach ([resource_path('views'), resource_path('js')] as $root) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root)
            );

            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $name = $file->getPathname();

                if (! preg_match('/\.(blade\.php|js)$/', $name)) {
                    continue;
                }

                /*
                 * The generated documents — PDFs, reports, exports and
                 * e-mails — never load the application stylesheet. Each
                 * carries its own <style> block, because dompdf and every
                 * e-mail client need it that way, so their classes are
                 * defined somewhere this audit does not read and should
                 * not try to.
                 */
                if (preg_match(
                    '#views[\\/](documents|reports|pdf|registry|emails'
                    .'|activity-log|financial-journal'
                    .'|lease-financial-history)[\\/]#',
                    $name
                )) {
                    continue;
                }

                /*
                 * Only what appears inside a class attribute or a class
                 * string. A pm- name in a data attribute or a comment is
                 * not markup asking to be drawn.
                 */
                preg_match_all(
                    '/class(?:List)?\s*[=:.]?\s*["\'`]([^"\'`]*)["\'`]|class="([^"]*)"/i',
                    (string) file_get_contents($name),
                    $matches
                );

                $lists = array_merge($matches[1], $matches[2]);

                foreach ($lists as $list) {
                    /*
                     * A `pm-` preceded by a hyphen is the tail of a
                     * --pm-* TOKEN inside an arbitrary-value utility
                     * such as bg-[var(--pm-surface)], not a class.
                     */
                    preg_match_all('/(?<![-\\w])pm-[a-z0-9-]+/', $list, $classes);

                    foreach ($classes[0] as $class) {
                        $used[$class][] = str_replace(
                            resource_path().DIRECTORY_SEPARATOR,
                            '',
                            $name
                        );
                    }
                }
            }
        }

        return $used;
    }
}

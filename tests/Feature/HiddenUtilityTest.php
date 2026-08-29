<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * An element told to be hidden must be hidden.
 *
 * Tailwind's utilities live in a cascade layer and Patrimoine's component
 * classes do not, so an unlayered component that sets `display` beats the
 * `hidden` utility however specific the utility looks. That is not a
 * theory: it left the lease wizard showing its create-a-new-property
 * fields while an existing property was selected, and its draft and
 * submit buttons showing on every step.
 *
 * resources/css/hidden-guards.css gives each such component a companion
 * rule. This test recomputes the list and fails when a component starts
 * setting `display` without one, because the symptom — a panel that will
 * not go away — never looks like a stylesheet problem from the outside.
 */
class HiddenUtilityTest extends TestCase
{
    /**
     * Component classes that set a display other than none.
     *
     * Deliberately the same reading as scripts/generate-hidden-guards.mjs;
     * if the two ever disagree the generator is the one to correct.
     *
     * @return array<int, string>
     */
    private function componentsThatSetDisplay(): array
    {
        $css = $this->stylesheet('app.css');

        /*
         * Comments first. A rule preceded by one would otherwise lose its
         * opening selector to the comment text.
         */
        $css = preg_replace('#/\*.*?\*/#s', '', $css);

        $css = str_replace("@import './hidden-guards.css';\n", '', $css);

        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $blocks, PREG_SET_ORDER);

        $found = [];

        foreach ($blocks as [, $selector, $body]) {
            if (preg_match('/(?:^|[;\s])display\s*:\s*([a-z-]+)/', $body, $display) !== 1) {
                continue;
            }

            if ($display[1] === 'none') {
                continue;
            }

            foreach (explode(',', $selector) as $part) {
                $part = trim($part);

                if (
                    ! str_contains($part, 'hidden')
                    && preg_match('/^\.((?:pm|shell|theme|rbac)[a-z0-9-]*)$/', $part, $name) === 1
                ) {
                    $found[$name[1]] = true;
                }
            }
        }

        $names = array_keys($found);

        sort($names);

        return $names;
    }

    private function stylesheet(string $name): string
    {
        return file_get_contents(
            resource_path('css/'.$name)
        );
    }

    public function test_every_component_that_sets_display_is_guarded(): void
    {
        $guards = $this->stylesheet('hidden-guards.css');

        $unguarded = [];

        foreach ($this->componentsThatSetDisplay() as $component) {
            if (! str_contains($guards, '.'.$component.'.hidden')) {
                $unguarded[] = $component;
            }
        }

        $this->assertSame(
            [],
            $unguarded,
            'These components would defeat the hidden utility. '
            .'Run: node scripts/generate-hidden-guards.mjs'
        );
    }

    public function test_the_guards_are_actually_loaded(): void
    {
        $this->assertStringContainsString(
            "@import './hidden-guards.css';",
            $this->stylesheet('app.css'),
            'The guards do nothing unless app.css pulls them in.'
        );

        $this->assertStringContainsString(
            'display: none;',
            $this->stylesheet('hidden-guards.css')
        );
    }

    public function test_the_guards_never_reach_for_important(): void
    {
        /*
         * `hidden xl:block` is a real pattern here — the top bar date —
         * and the utility that reveals it is layered like `hidden` is. An
         * !important guard would win against that too and the element
         * would never appear.
         */
        $this->assertStringNotContainsString(
            '!important',
            $this->stylesheet('hidden-guards.css')
        );

        $this->assertStringContainsString(
            'xl:block',
            file_get_contents(
                resource_path('views/layouts/app.blade.php')
            ),
            'The pattern this restraint protects should still exist; '
            .'if it does not, re-read the reasoning before relaxing it.'
        );
    }

    public function test_the_wizard_panels_that_prompted_this_are_covered(): void
    {
        $guards = $this->stylesheet('hidden-guards.css');

        foreach ([
            'pm-wizard-subfields',
            'pm-wizard-grid',
            'pm-button-primary',
            'pm-button-secondary',
            'pm-input',
        ] as $component) {
            $this->assertStringContainsString(
                '.'.$component.'.hidden',
                $guards
            );
        }
    }
}

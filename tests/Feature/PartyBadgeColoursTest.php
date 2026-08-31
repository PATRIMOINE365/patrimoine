<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A party type and a party role are always drawn in their own colour.
 *
 * The colours are the point: a list of parties is read by scanning it, and
 * six identical grey pills tell a reader nothing. What matters as much is
 * that they never drift — a tenant chip is the same blue on the parties
 * list as anywhere else it appears — so the mapping lives in one helper
 * and the colours live in one token block.
 *
 * The class names are built from a value at runtime, which the markup
 * scanner cannot follow, so this is what proves the six exist.
 */
class PartyBadgeColoursTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function kinds(): array
    {
        return [
            'person',
            'organisation',
            'association',
            'tenant',
            'owner',
            'agent',
        ];
    }

    public function test_every_type_and_role_has_a_badge_class(): void
    {
        $components = file_get_contents(
            resource_path('css/components.css')
        );

        foreach ($this->kinds() as $kind) {
            $this->assertMatchesRegularExpression(
                '/^\.pm-badge-'.preg_quote($kind, '/').'\s*\{/m',
                $components,
                'pm-badge-'.$kind.' has no rule, so that chip would be colourless.'
            );
        }
    }

    /**
     * Six chips a reader is meant to tell apart cannot share a colour.
     */
    public function test_no_two_of_them_share_a_colour(): void
    {
        $components = file_get_contents(
            resource_path('css/components.css')
        );

        $backgrounds = [];

        foreach ($this->kinds() as $kind) {
            preg_match(
                '/\.pm-badge-'.preg_quote($kind, '/')
                .'\s*\{[^}]*background:\s*([^;]+);/m',
                $components,
                $matches
            );

            $this->assertNotEmpty(
                $matches,
                'pm-badge-'.$kind.' does not set a background.'
            );

            $backgrounds[$kind] = trim($matches[1]);
        }

        $this->assertSame(
            count($backgrounds),
            count(array_unique($backgrounds)),
            'Two party chips are drawn in the same colour: '
            .json_encode($backgrounds)
        );
    }

    /**
     * Both themes, or half of them vanish in the dark.
     */
    public function test_the_colours_are_defined_for_both_themes(): void
    {
        $tokens = file_get_contents(
            resource_path('css/tokens.css')
        );

        foreach (
            [
                'type-person',
                'type-organisation',
                'type-association',
                'role-tenant',
                'role-owner',
                'role-agent',
            ] as $token
        ) {
            $this->assertSame(
                2,
                substr_count($tokens, '--pm-'.$token.'-background:'),
                '--pm-'.$token.'-background is not defined in both themes.'
            );
        }
    }

    /**
     * The mapping is in one place, not written out at each call site.
     */
    public function test_the_mapping_lives_in_one_helper(): void
    {
        $script = file_get_contents(
            resource_path('js/parties.js')
        );

        $this->assertStringContainsString(
            'function partyBadgeClass(',
            $script
        );

        /*
         * The declaration plus at least the two call sites it exists for:
         * the type chip and the role chip. More is better — every other
         * place a party is chipped should come through here too.
         */
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($script, 'partyBadgeClass('),
            'The type chip and the role chip should both go through the helper.'
        );
    }
}

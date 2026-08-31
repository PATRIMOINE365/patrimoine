<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every capability the markup gates on must be one the browser knows.
 *
 * The gates work by hiding an element until the permissions module reveals
 * it, so a `data-requires-capability` naming a capability that has no
 * entry in that module is not "ungated" — it is hidden FOREVER, for
 * everybody, because nothing ever comes along to show it.
 *
 * The archive page shipped exactly that way: it gates on view_operations,
 * which every role carries, and it never appeared for anyone.
 */
class CapabilityGatesAreCompleteTest extends TestCase
{
    public function test_every_gated_capability_can_be_revealed(): void
    {
        $permissions = file_get_contents(
            resource_path('js/permissions.js')
        );

        $used = [];

        foreach (
            array_merge(
                glob(resource_path('views/app/*.blade.php')) ?: [],
                glob(resource_path('views/app/**/*.blade.php')) ?: [],
                glob(resource_path('views/layouts/*.blade.php')) ?: [],
                glob(resource_path('js/*.js')) ?: []
            ) as $file
        ) {
            preg_match_all(
                '/data-requires-capability="([a-z_]+)"/',
                (string) file_get_contents($file),
                $matches
            );

            foreach ($matches[1] as $capability) {
                $used[$capability] = true;
            }
        }

        $this->assertNotEmpty(
            $used,
            'No gated capabilities were found, so nothing was checked.'
        );

        $unrevealable = [];

        foreach (array_keys($used) as $capability) {
            if (
                ! preg_match(
                    '/^\s{4}'.preg_quote($capability, '/').':\s*\[/m',
                    $permissions
                )
            ) {
                $unrevealable[] = $capability;
            }
        }

        sort($unrevealable);

        $this->assertSame(
            [],
            $unrevealable,
            'These capabilities are gated in markup but have no entry in '
            .'permissions.js, so anything declaring them is hidden from '
            .'everybody: '.implode(', ', $unrevealable)
        );
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The tenant workspace closes every drawer it draws.
 *
 * Most of Patrimoine wires a drawer through core.js's wireDrawer(), which
 * finds the backdrop by its class and handles Escape itself. The tenant
 * workspace does not: it predates that helper and wires its drawers from a
 * list of ids in initializeTenantTransactionControls().
 *
 * A list is only as good as the last person to remember it. It originally
 * built its ids from `tenant-${action}-drawer`, which silently excluded the
 * two drawers on that page whose ids do not begin that way — Pay Invoice
 * and Cancel payment. Escape still closed them, because the Escape handler
 * lists drawers by their real ids, but the cross in the header and the
 * backdrop behind it did nothing at all. Somebody who pressed the cross
 * concluded the screen was stuck, which is the correct conclusion to draw
 * from a cross that does nothing.
 *
 * So this compares the two lists directly: every drawer rendered by
 * tenants.blade.php must appear in the list that wires them shut. It is a
 * narrow test on purpose — it does not try to prove a listener behaves,
 * only that no drawer on that page has been left out of the list again.
 */
class DrawersCanBeClosedTest extends TestCase
{
    public function test_the_tenant_workspace_wires_every_drawer_it_draws(): void
    {
        $drawn = $this->drawersInTenantsMarkup();

        $this->assertNotEmpty(
            $drawn,
            'No drawers were found in tenants.blade.php, so this test is reading the wrong file.'
        );

        $wired = $this->drawersWiredForClosing();

        $this->assertNotEmpty(
            $wired,
            'The drawer list in initializeTenantTransactionControls() could not be read.'
        );

        $missing = array_values(array_diff($drawn, $wired));

        $this->assertSame(
            [],
            $missing,
            "These tenant drawers are drawn but never wired to close:\n  "
                .implode("\n  ", $missing)
        );
    }

    /**
     * Escape closes them too, and by their real ids.
     *
     * The Escape handler is a second hand-maintained list of the same
     * drawers, which is a second place to forget one.
     */
    public function test_escape_closes_every_tenant_drawer(): void
    {
        $scripts = $this->tenantScript();

        $missing = [];

        foreach ($this->drawersInTenantsMarkup() as $drawer) {
            if (! str_contains($scripts, '#'.$drawer.'.pm-drawer-active')) {
                $missing[] = $drawer;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Escape does not close these tenant drawers:\n  "
                .implode("\n  ", $missing)
        );
    }

    /**
     * Every drawer id rendered by the tenant workspace.
     *
     * @return array<int, string>
     */
    private function drawersInTenantsMarkup(): array
    {
        $markup = (string) file_get_contents(
            resource_path('views/app/tenants.blade.php')
        );

        preg_match_all(
            '/<x-drawer\s+id="([a-z0-9-]+)"/',
            $markup,
            $found
        );

        return $found[1];
    }

    /**
     * The drawer ids initializeTenantTransactionControls() closes.
     *
     * @return array<int, string>
     */
    private function drawersWiredForClosing(): array
    {
        $scripts = $this->tenantScript();

        if (! preg_match(
            '/\[\s*((?:\s*\'[a-z0-9-]+\',\s*)+)\]\.forEach\(\s*\(drawer\)/s',
            $scripts,
            $found
        )) {
            return [];
        }

        preg_match_all("/'([a-z0-9-]+)'/", $found[1], $ids);

        return $ids[1];
    }

    private function tenantScript(): string
    {
        return (string) file_get_contents(
            resource_path('js/tenants.js')
        );
    }
}

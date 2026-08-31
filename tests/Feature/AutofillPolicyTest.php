<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The browser fills in the sign-in and sign-up pages, and nothing else.
 *
 * Inside the application the fields are not the operator's own details —
 * they are a tenant's telephone number, an owner's e-mail address, a
 * property's address. The browser cannot tell the difference and offers
 * what it has saved, so a stray keystroke can put the operator's own
 * address into somebody else's record without anybody noticing.
 *
 * The policy is asked for by the shell rather than assumed by the script,
 * because every page in Patrimoine — the auth pages included — loads the
 * same bundle.
 */
class AutofillPolicyTest extends TestCase
{
    private function layout(string $name): string
    {
        return file_get_contents(
            resource_path('views/layouts/'.$name.'.blade.php')
        );
    }

    public function test_both_application_shells_refuse_autofill(): void
    {
        foreach (['app', 'admin'] as $shell) {
            $this->assertStringContainsString(
                'data-autofill="off"',
                $this->layout($shell),
                'The '.$shell.' shell does not refuse browser autofill.'
            );
        }
    }

    /**
     * The sign-in and sign-up pages must keep it.
     */
    public function test_the_auth_pages_still_allow_autofill(): void
    {
        $this->assertStringNotContainsString(
            'data-autofill="off"',
            $this->layout('auth'),
            'A password manager has to be able to fill in sign-in and sign-up.'
        );
    }

    /**
     * A one-off pass at boot would cover almost nothing: nearly every form
     * in Patrimoine is inside a drawer that renders when it opens.
     */
    public function test_fields_rendered_later_are_covered_too(): void
    {
        $script = file_get_contents(
            resource_path('js/autofill.js')
        );

        $this->assertStringContainsString(
            'MutationObserver',
            $script,
            'Fields rendered into a drawer after boot would not be covered.'
        );

        $this->assertStringContainsString(
            'subtree: true',
            $script
        );

        /*
         * A password box inside the application is always a confirmation
         * of the signed-in user's own password before something
         * irreversible. `off` is advice; `new-password` is an instruction.
         */
        $this->assertStringContainsString(
            "'new-password'",
            $script,
            'Password fields need the stronger token, not off.'
        );
    }

    public function test_the_policy_is_wired_into_the_bundle(): void
    {
        $app = file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertStringContainsString(
            'initializeAutofillPolicy()',
            $app,
            'The policy exists but nothing calls it.'
        );
    }
}

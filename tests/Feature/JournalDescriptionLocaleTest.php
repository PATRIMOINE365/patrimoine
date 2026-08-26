<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Financial Journal entry descriptions are written in the organisation's
 * own language at posting time.
 *
 * They used to be hardcoded English literals inside the accounting
 * services, so a French organisation read English text in its own
 * journal. These tests guard both halves of the fix: the translations
 * exist in both languages, and no service has quietly reintroduced a
 * hardcoded description.
 */
class JournalDescriptionLocaleTest extends TestCase
{
    public function test_every_description_exists_in_both_languages(): void
    {
        $english = trans('financial_journal.descriptions', [], 'en');
        $french = trans('financial_journal.descriptions', [], 'fr');

        $this->assertIsArray($english);
        $this->assertNotEmpty($english);

        $this->assertSame(
            array_keys($english),
            array_keys($french),
            'The French journal descriptions must cover exactly the English keys.'
        );

        foreach ($french as $key => $value) {
            $this->assertNotSame(
                $english[$key],
                $value,
                "The French description for [{$key}] is still the English text."
            );
        }
    }

    public function test_descriptions_render_in_the_active_language(): void
    {
        $this->app->setLocale('en');

        $this->assertSame(
            'Rent invoice INV-000042',
            __(
                'financial_journal.descriptions.rent_invoice',
                ['reference' => 'INV-000042']
            )
        );

        $this->app->setLocale('fr');

        $french = __(
            'financial_journal.descriptions.rent_invoice',
            ['reference' => 'INV-000042']
        );

        $this->assertStringContainsString('Facture de loyer', $french);
        $this->assertStringContainsString('INV-000042', $french);
    }

    public function test_placeholders_are_substituted_not_left_raw(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $this->app->setLocale($locale);

            $rendered = __(
                'financial_journal.descriptions.owner_rent_entitlement',
                ['reference' => 4242]
            );

            $this->assertStringContainsString('4242', $rendered);
            $this->assertStringNotContainsString(':reference', $rendered);
        }
    }

    public function test_no_accounting_service_hardcodes_a_description(): void
    {
        $directory = app_path('Services/Accounting');

        /*
         * The literals that used to be written straight onto the entry.
         * Any of them reappearing means a code path bypassed the
         * translations again.
         */
        $forbidden = [
            "'Owner Deposit #'",
            "'Owner Payout #'",
            "'Owner rent entitlement for Payment Allocation #'",
            "'Management Fee for Payment Allocation #'",
            "'Owner Account balance adjustment.'",
            "'Rent invoice '",
            "'Rent receipt allocation #'",
            "'Security Deposit applied '",
            "'Security Deposit debt invoice '",
            "'Security Deposit refund '",
            "'Expense Invoice settlement #'",
            "'Tenant fund balance adjustment.'",
            "'Tenant fund Withdrawal #'",
            "'Tenant fund transfer.'",
            "'Rent Reserve consumption'",
            "'Consumable Advance consumption'",
            "'Rent Reserve funding'",
            "'Consumable Advance funding'",
            "'Security Deposit funding'",
            "'Tenant fund funding'",
            "'Reversal of %s: %s'",
        ];

        $offenders = [];

        foreach (glob($directory.'/*.php') as $file) {
            $contents = (string) file_get_contents($file);

            foreach ($forbidden as $literal) {
                if (str_contains($contents, $literal)) {
                    $offenders[] = basename($file).' → '.$literal;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Journal descriptions must come from translations:\n".implode("\n", $offenders)
        );
    }
}

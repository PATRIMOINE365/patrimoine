<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Activity Log workspace must read in the organisation's language.
 *
 * Action names, record types and metadata keys are rendered by the
 * browser, which cannot call __(). They are therefore mirrored from
 * lang/{en,fr}/activity_log.php into resources/js/translations.js — and
 * a mirror is only safe while something fails when it drifts.
 *
 * That is what this test is for. Adding an action to the PHP catalogue
 * without mirroring it fails here rather than silently showing a French
 * organisation an English label, which is exactly how the gap this test
 * closes went unnoticed.
 */
class ActivityLogLocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every key of one PHP group, mirrored under a browser prefix.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function mirroredGroups(): array
    {
        return [
            ['actions', 'activity_actions'],
            ['entities', 'activity_entities'],
            ['metadata_labels', 'activity_metadata'],
        ];
    }

    /**
     * The browser catalogue as raw text: it is a JavaScript module, so
     * the assertion is a presence check on the quoted key.
     */
    private function browserCatalogue(): string
    {
        return file_get_contents(
            resource_path('js/translations.js')
        );
    }

    /**
     * Every catalogued action, record type and metadata key reaches the
     * browser in both languages.
     */
    public function test_every_activity_label_is_mirrored_to_the_browser(): void
    {
        $catalogue = $this->browserCatalogue();

        $missing = [];

        foreach ($this->mirroredGroups() as [$group, $prefix]) {
            $english = require base_path("lang/en/activity_log.php");
            $french = require base_path("lang/fr/activity_log.php");

            foreach (array_keys($english[$group]) as $key) {
                /*
                 * Both halves of translations.js hold the same key, so a
                 * single occurrence means one language is missing it.
                 */
                $occurrences = substr_count(
                    $catalogue,
                    "'{$prefix}.{$key}':"
                );

                if ($occurrences < 2) {
                    $missing[] = "{$prefix}.{$key} (found {$occurrences} of 2)";
                }

                if (! array_key_exists($key, $french[$group])) {
                    $missing[] = "lang/fr {$group}.{$key}";
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Activity Log labels missing from the browser catalogue:\n"
                .implode("\n", $missing)
        );
    }

    /**
     * The workspace resolves those labels rather than prettifying the
     * raw identifier, which is what produced English text inside a
     * French organisation.
     */
    public function test_the_workspace_resolves_labels_instead_of_humanising(): void
    {
        $module = file_get_contents(
            resource_path('js/activity-log.js')
        );

        foreach (
            [
                'activityActionLabel(',
                'activityEntityLabel(',
                'activityMetadataLabel(',
            ] as $helper
        ) {
            $this->assertStringContainsString(
                $helper,
                $module,
                "activity-log.js no longer uses {$helper}."
            );
        }

        /*
         * humanizeIdentifier survives as the fallback inside those
         * helpers only. Rendering an action or a record type with it
         * directly is the regression this guards.
         */
        foreach (
            [
                "humanizeIdentifier(\n                                    event.action",
                "humanizeIdentifier(\n                            event.entity_type",
            ] as $forbidden
        ) {
            $this->assertStringNotContainsString(
                $forbidden,
                $module,
                'An Activity Log label is being humanised instead of translated.'
            );
        }
    }

    /**
     * The Financial Journal's transaction types are mirrored the same
     * way, and drifted the same way: V1.0.23 added management_fee_vat to
     * the PHP catalogue only, so the filter offered "Management Fee Vat"
     * to a French organisation.
     */
    public function test_every_transaction_type_is_mirrored_to_the_browser(): void
    {
        $catalogue = file_get_contents(
            resource_path('js/translations.js')
        );

        $english = require base_path('lang/en/financial_journal.php');
        $french = require base_path('lang/fr/financial_journal.php');

        $missing = [];

        foreach (array_keys($english['transaction_types']) as $type) {
            $occurrences = substr_count(
                $catalogue,
                "'financial_journal.transaction_types.{$type}':"
            );

            if ($occurrences < 2) {
                $missing[] = "browser: {$type} (found {$occurrences} of 2)";
            }

            if (! array_key_exists($type, $french['transaction_types'])) {
                $missing[] = "lang/fr: {$type}";
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Transaction types missing a translation:\n"
                .implode("\n", $missing)
        );
    }

    /**
     * The Financial Journal had the same gap in its filter and detail
     * panel after V1.0.20 localised its rows.
     */
    public function test_the_financial_journal_resolves_transaction_types(): void
    {
        $module = file_get_contents(
            resource_path('js/financial-journal.js')
        );

        $this->assertStringNotContainsString(
            "humanizeIdentifier(\n                                type\n                            )",
            $module,
            'The transaction-type filter is humanising instead of translating.'
        );

        $this->assertStringNotContainsString(
            "humanizeIdentifier(\n                            entry.transaction_type",
            $module,
            'The journal detail panel is humanising instead of translating.'
        );
    }
}

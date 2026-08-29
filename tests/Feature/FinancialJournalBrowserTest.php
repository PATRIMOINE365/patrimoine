<?php

namespace Tests\Feature;

use Tests\TestCase;

class FinancialJournalBrowserTest extends TestCase
{
    public function test_financial_journal_page_is_available(): void
    {
        $this
            ->get('/financial-journal')
            ->assertOk()
            ->assertSee(
                'id="financial-journal-workspace"',
                false
            )
            ->assertSee(
                'data-requires-capability="view_financial_journal"',
                false
            );
    }

    /**
     * V1.0.31 reordered the Manage group at Komla's request: the things an
     * administrator sets up come first, the things they read come last.
     */
    public function test_manage_lists_its_links_in_the_settled_order(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        $order = [
            '/settings',
            '/users',
            '/license',
            '/activity-log',
            '/financial-journal',
        ];

        $positions = [];

        foreach ($order as $href) {
            $at = strpos(
                $view,
                'href="'.$href.'"'
            );

            $this->assertNotFalse(
                $at,
                $href.' is missing from the sidebar.'
            );

            $positions[$href] = $at;
        }

        $sorted = $positions;

        asort($sorted);

        $this->assertSame(
            $order,
            array_keys($sorted),
            'The Manage links are not in the order they were asked for.'
        );

        $this->assertStringContainsString(
            'data-requires-capability="view_financial_journal"',
            $view
        );

        $this->assertStringContainsString(
            'data-i18n="navigation.financial_journal"',
            $view
        );
    }

    public function test_financial_journal_module_is_wired_into_browser_bundle(): void
    {
        $app =
            file_get_contents(
                resource_path(
                    'js/app.js'
                )
            );

        $javascript =
            file_get_contents(
                resource_path(
                    'js/financial-journal.js'
                )
            );

        $this->assertStringContainsString(
            "from './financial-journal.js'",
            $app
        );

        $this->assertStringContainsString(
            'await initializeFinancialJournal();',
            $app
        );

        $this->assertStringContainsString(
            '/api/financial-journal/filter-options',
            $javascript
        );

        $this->assertStringContainsString(
            '/api/financial-journal?',
            $javascript
        );
    }

    public function test_financial_journal_ui_exposes_register_filters_and_exports(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/financial-journal.blade.php'
                )
            );

        foreach (
            [
                'financial-journal-search',
                'financial-journal-from',
                'financial-journal-to',
                'financial-journal-entry-kind',
                'financial-journal-transaction-type',
                'financial-journal-account',
                'financial-journal-clear-filters',
                'financial-journal-list',
                'financial-journal-pagination',
                'financial-journal-export-pdf',
                'financial-journal-export-csv',
                'financial-journal-export-xlsx',
            ] as $id
        ) {
            $this->assertStringContainsString(
                'id="'.$id.'"',
                $view
            );
        }
    }

    public function test_financial_journal_uses_read_only_detail_drawer(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/financial-journal.blade.php'
                )
            );

        $javascript =
            file_get_contents(
                resource_path(
                    'js/financial-journal.js'
                )
            );

        foreach (
            [
                'financial-journal-modal',
                'financial-journal-modal-backdrop',
                'financial-journal-modal-close',
                'financial-journal-detail',
            ] as $id
        ) {
            $this->assertStringContainsString(
                $id,
                $view
            );
        }

        $this->assertStringContainsString(
            'account_code_snapshot',
            $javascript
        );

        $this->assertStringContainsString(
            'account_name_snapshot',
            $javascript
        );

        $this->assertStringContainsString(
            'debit_amount',
            $javascript
        );

        $this->assertStringContainsString(
            'credit_amount',
            $javascript
        );

        $this->assertStringContainsString(
            'reversal_of',
            $javascript
        );

        $this->assertStringContainsString(
            'reversed_by',
            $javascript
        );
    }

    public function test_financial_journal_exports_preserve_active_filters(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/financial-journal.js'
                )
            );

        $this->assertStringContainsString(
            'financialJournalFilterParameters',
            $javascript
        );

        foreach (
            [
                '/api/financial-journal/${format}',
                "'pdf'",
                "'csv'",
                "'xlsx'",
            ] as $marker
        ) {
            $this->assertStringContainsString(
                $marker,
                $javascript
            );
        }
    }

    public function test_financial_journal_browser_translations_exist_in_both_languages(): void
    {
        $translations =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        foreach (
            [
                "'navigation.financial_journal':",
                "'financial_journal.title':",
                "'financial_journal.search':",
                "'financial_journal.entry_kind':",
                "'financial_journal.transaction_type':",
                "'financial_journal.account':",
                "'financial_journal.export_pdf':",
                "'financial_journal.export_csv':",
                "'financial_journal.export_xlsx':",
                "'financial_journal.accounting_lines':",
                "'financial_journal.reversal_context':",
            ] as $key
        ) {
            $this->assertSame(
                2,
                substr_count(
                    $translations,
                    $key
                ),
                "Expected {$key} exactly once in each language catalogue."
            );
        }
    }

    public function test_financial_journal_browser_is_strictly_read_only(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/financial-journal.blade.php'
                )
            );

        $javascript =
            file_get_contents(
                resource_path(
                    'js/financial-journal.js'
                )
            );

        foreach (
            [
                'journal-delete',
                'journal-edit',
                'journal-post',
                'journal-reverse',
                'financial-journal-delete',
                'financial-journal-edit',
                'financial-journal-post',
                'financial-journal-reverse',
            ] as $marker
        ) {
            $this->assertStringNotContainsString(
                $marker,
                $view
            );

            $this->assertStringNotContainsString(
                $marker,
                $javascript
            );
        }

        foreach (
            [
                "method: 'POST'",
                "method: 'PUT'",
                "method: 'PATCH'",
                "method: 'DELETE'",
            ] as $marker
        ) {
            $this->assertStringNotContainsString(
                $marker,
                $javascript
            );
        }

        $this->assertStringNotContainsString(
            'running balance',
            strtolower(
                $view
                .$javascript
            )
        );
    }


    public function test_financial_journal_workspace_is_wired_to_browser_capability(): void
    {
        $permissions = file_get_contents(
            resource_path('js/permissions.js')
        );

        $this->assertIsString($permissions);

        $this->assertStringContainsString(
            "'view_financial_journal'",
            $permissions
        );

        $this->assertStringContainsString(
            'view_financial_journal: [',
            $permissions
        );

        $this->assertStringContainsString(
            '\'[data-requires-capability="view_financial_journal"]\'',
            $permissions
        );
    }

    public function test_financial_journal_direct_route_is_browser_restricted(): void
    {
        $permissions = file_get_contents(
            resource_path('js/permissions.js')
        );

        $this->assertIsString($permissions);

        $this->assertStringContainsString(
            "'/financial-journal':",
            $permissions
        );

        $this->assertStringContainsString(
            "'view_financial_journal'",
            $permissions
        );
    }

    public function test_financial_journal_search_placeholder_uses_translation_key_contract(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/app/financial-journal.blade.php'
            )
        );

        $this->assertIsString($view);

        $this->assertStringContainsString(
            'data-i18n-placeholder="financial_journal.search_placeholder"',
            $view
        );

        $this->assertStringNotContainsString(
            'data-i18n-placeholder="{{ __(\'ui.financial_journal.search_placeholder\') }}"',
            $view
        );
    }



    public function test_financial_journal_detail_matches_tenant_deposit_drawer_width(): void
    {
        $journal = file_get_contents(
            resource_path(
                'views/app/financial-journal.blade.php'
            )
        );

        $tenants = file_get_contents(
            resource_path(
                'views/app/tenants.blade.php'
            )
        );

        $this->assertIsString($journal);
        $this->assertIsString($tenants);

        $this->assertMatchesRegularExpression(
            '/id="financial-journal-modal"[\s\S]*?width="sm"/',
            $journal
        );

        $this->assertMatchesRegularExpression(
            '/id="tenant-deposit-drawer"[\s\S]*?width="sm"/',
            $tenants
        );
    }

    public function test_financial_journal_detail_uses_single_column_layout(): void
    {
        $javascript = file_get_contents(
            resource_path(
                'js/financial-journal.js'
            )
        );

        $this->assertIsString($javascript);

        $detailStart = strpos(
            $javascript,
            'function financialJournalDetailMarkup'
        );

        $detailEnd = strpos(
            $javascript,
            'function financialJournalDetailItem',
            $detailStart
        );

        $this->assertNotFalse($detailStart);
        $this->assertNotFalse($detailEnd);

        $detail = substr(
            $javascript,
            $detailStart,
            $detailEnd - $detailStart
        );

        $this->assertStringNotContainsString(
            'sm:grid-cols-2',
            $detail
        );

        $this->assertStringNotContainsString(
            'grid-cols-2 gap-3',
            $detail
        );

        $this->assertStringNotContainsString(
            'md:grid-cols-[minmax(0,1fr)_auto_auto]',
            $detail
        );
    }

}

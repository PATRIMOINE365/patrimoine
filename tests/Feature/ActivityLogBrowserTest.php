<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Freeze the Activity Q browser integration surface.
 *
 * Server-side authorization and filtering are covered by ActivityLogApiTest.
 * These assertions protect the UI route, navigation, JavaScript and i18n
 * wiring without introducing write behavior into Activity Log.
 */
class ActivityLogBrowserTest extends TestCase
{
    public function test_activity_log_page_is_available(): void
    {
        $this
            ->get('/activity-log')
            ->assertOk()
            ->assertSee(
                'id="activity-log-workspace"',
                false
            )
            ->assertSee(
                'data-requires-capability="view_activity_log"',
                false
            )
            ->assertSee(
                'id="activity-log-search"',
                false
            )
            ->assertSee(
                'id="activity-log-list"',
                false
            )
            ->assertSee(
                'id="activity-log-modal"',
                false
            );
    }

    public function test_layout_contains_hidden_activity_log_navigation(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        $this->assertStringContainsString(
            'href="/activity-log"',
            $view
        );

        $this->assertStringContainsString(
            'data-requires-capability="view_activity_log"',
            $view
        );

        $this->assertStringContainsString(
            'label="navigation.activity_log"',
            $view
        );

        $this->assertStringContainsString(
            'rbac-hidden',
            $view
        );
    }

    public function test_activity_log_module_is_wired_into_browser_bundle(): void
    {
        $app =
            file_get_contents(
                resource_path(
                    'js/app.js'
                )
            );

        $activity =
            file_get_contents(
                resource_path(
                    'js/activity-log.js'
                )
            );

        $this->assertStringContainsString(
            "from './activity-log.js'",
            $app
        );

        $this->assertStringContainsString(
            'await initializeActivityLog();',
            $app
        );

        $this->assertStringContainsString(
            '/api/activity-log',
            $activity
        );

        $this->assertStringContainsString(
            '/api/users?per_page=100',
            $activity
        );

        $this->assertStringContainsString(
            'getPresentationConfiguration',
            $activity
        );

        $this->assertStringContainsString(
            'Intl.DateTimeFormat',
            $activity
        );
    }

    public function test_activity_log_ui_exposes_frozen_q_filters(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/activity-log.blade.php'
                )
            );

        foreach (
            [
                'activity-log-search',
                'activity-log-from',
                'activity-log-to',
                'activity-log-user',
                'activity-log-role',
                'activity-log-action',
                'activity-log-entity-type',
            ] as $id
        ) {
            $this->assertStringContainsString(
                'id="'.$id.'"',
                $view
            );
        }
    }

    public function test_activity_log_ui_is_read_only(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/activity-log.blade.php'
                )
            );

        $javascript =
            file_get_contents(
                resource_path(
                    'js/activity-log.js'
                )
            );

        $this->assertStringNotContainsString(
            'data-delete-activity',
            $view
        );

        $this->assertStringNotContainsString(
            'data-edit-activity',
            $view
        );

        $this->assertStringNotContainsString(
            'postJson',
            $javascript
        );

        $this->assertStringNotContainsString(
            'patchJson',
            $javascript
        );

        $this->assertStringNotContainsString(
            'deleteJson',
            $javascript
        );
    }

    public function test_activity_log_exposes_filtered_pdf_and_csv_exports(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/app/activity-log.blade.php'
                )
            );

        $javascript =
            file_get_contents(
                resource_path(
                    'js/activity-log.js'
                )
            );

        /*
         * V1.0.35: no PDF button here. The export rendered every matching
         * row through dompdf, which holds the whole document in memory,
         * and the Activity Log is kept indefinitely — so it was a button
         * that would start answering 500 for every organisation in turn.
         */
        $this->assertStringNotContainsString(
            'id="activity-log-export-pdf"',
            $view
        );

        $this->assertStringContainsString(
            'id="activity-log-export-csv"',
            $view
        );

        $this->assertStringContainsString(
            'initializeExportActions();',
            $javascript
        );

        $this->assertStringContainsString(
            'function activityFilterParameters()',
            $javascript
        );

        $this->assertStringContainsString(
            '`/api/activity-log/${format}`',
            $javascript
        );

        $this->assertStringContainsString(
            'await response.blob();',
            $javascript
        );

        $this->assertStringContainsString(
            'Content-Disposition',
            $javascript
        );

        $this->assertStringContainsString(
            'activity-log.${format}',
            $javascript
        );
    }

    public function test_activity_log_exports_use_filters_without_browser_pagination(): void
    {
        $javascript =
            file_get_contents(
                resource_path(
                    'js/activity-log.js'
                )
            );

        $filterStart =
            strpos(
                $javascript,
                'function activityFilterParameters()'
            );

        $paginationStart =
            strpos(
                $javascript,
                'function activityQueryParameters('
            );

        $this->assertNotFalse(
            $filterStart
        );

        $this->assertNotFalse(
            $paginationStart
        );

        $filters =
            substr(
                $javascript,
                $filterStart,
                $paginationStart - $filterStart
            );

        foreach (
            [
                'search:',
                'from:',
                'to:',
                'user_id:',
                'role:',
                'action:',
                'entity_type:',
            ] as $filter
        ) {
            $this->assertStringContainsString(
                $filter,
                $filters
            );
        }

        $this->assertStringNotContainsString(
            "'page'",
            $filters
        );

        $this->assertStringNotContainsString(
            "'per_page'",
            $filters
        );
    }

    public function test_activity_log_translations_exist_in_both_languages(): void
    {
        $translations =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        foreach (
            [
                "'navigation.activity_log':",
                "'activity_log.title':",
                "'activity_log.heading':",
                "'activity_log.search':",
                "'activity_log.view_details':",
                "'activity_log.before_values':",
                "'activity_log.after_values':",
                "'activity_log.metadata':",
                "'activity_log.export_csv':",
                "'activity_log.exporting':",
                "'activity_log.unable_export':",
            ] as $key
        ) {
            $this->assertSame(
                2,
                substr_count(
                    $translations,
                    $key
                ),
                "Expected {$key} in both browser catalogues."
            );
        }

        $this->assertStringContainsString(
            "'Activity Log'",
            $translations
        );

        $this->assertStringContainsString(
            "'Journal d’activité'",
            $translations
        );
    }
}

<?php

namespace App\Services;

use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use IntlDateFormatter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Prepare immutable Activity Log events for human-facing exports.
 *
 * This service owns Activity Log export presentation only. It performs no
 * Activity Log writes and no filtering/query authorization.
 *
 * PDF and CSV exports consume the same normalized projection so their
 * interpretation of historical events cannot drift apart.
 */
class ActivityLogExportService
{
    public function __construct(
        private readonly ApplicationLocaleService $localeService
    ) {}

    /**
     * Convert Activity Log events into one shared export projection.
     *
     * Frozen actor_* fields are deliberately used instead of the current
     * User relationship because historical identity must survive later
     * user edits or deletion.
     *
     * @param  Collection<int, ActivityLog>  $events
     * @return array<int, array<string, string>>
     */
    public function rows(Collection $events): array
    {
        return $events
            ->map(
                fn (ActivityLog $event): array => $this->row($event)
            )
            ->values()
            ->all();
    }

    /**
     * Normalize one immutable Activity Log event for export presentation.
     *
     * @return array<string, string>
     */
    public function row(ActivityLog $event): array
    {
        return [
            'id' => (string) $event->getKey(),

            'timestamp' => $this->timestamp(
                $event->created_at
            ),

            'actor_name' => $this->text(
                $event->actor_name,
                $this->translation(
                    'activity_log.unknown_actor',
                    'Unknown actor'
                )
            ),

            'actor_email' => $this->text(
                $event->actor_email
            ),

            'actor_role' => $this->role(
                $event->actor_role
            ),

            'action' => $this->action(
                $event->action
            ),

            'entity_type' => $this->entityType(
                $event->entity_type
            ),

            'entity_id' => $this->text(
                $event->entity_id
            ),

            'entity_label' => $this->text(
                $event->entity_label
            ),

            'ip_address' => $this->text(
                $event->ip_address
            ),

            'browser' => $this->text(
                $event->browser
            ),

            'platform' => $this->text(
                $event->platform
            ),

            'device' => $this->text(
                $event->device
            ),

            'before_values' => $this->structured(
                $event->before_values
            ),

            'after_values' => $this->structured(
                $event->after_values
            ),

            'snapshot' => $this->structured(
                $event->snapshot
            ),

            'metadata' => $this->structured(
                $event->metadata
            ),
        ];
    }

    /**
     * Stable export columns in their human-facing order.
     *
     * @return array<string, string>
     */
    public function columns(): array
    {
        $columns =
            $this->translationArray(
                'activity_log.columns'
            );

        $definitions = [
            'id' => 'Event ID',
            'timestamp' => 'Timestamp',
            'actor_name' => 'Actor',
            'actor_email' => 'Actor Email',
            'actor_role' => 'Role',
            'action' => 'Action',
            'entity_type' => 'Record Type',
            'entity_id' => 'Record ID',
            'entity_label' => 'Record',
            'ip_address' => 'IP Address',
            'browser' => 'Browser',
            'platform' => 'Platform',
            'device' => 'Device',
            'before_values' => 'Before Values',
            'after_values' => 'After Values',
            'snapshot' => 'Snapshot',
            'metadata' => 'Metadata',
        ];

        foreach ($definitions as $key => $fallback) {
            $definitions[$key] =
                $this->text(
                    $columns[$key] ?? null,
                    $fallback
                );
        }

        return $definitions;
    }

    /**
     * Render the complete matching Activity Log result as CSV.
     *
     * CSV consumes exactly the same normalized localized projection as PDF.
     * No Activity Log query, authorization or write behavior belongs here.
     *
     * @param  Collection<int, ActivityLog>  $events
     */
    public function csv(
        Collection $events
    ): string {
        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new \RuntimeException(
                'Unable to create Activity Log CSV stream.'
            );
        }

        $columns =
            $this->columns();

        /*
         * Human-facing translated labels form the CSV header while the
         * stable internal column keys continue to control row ordering.
         */
        fputcsv(
            $stream,
            array_values(
                $columns
            )
        );

        foreach (
            $this->rows(
                $events
            ) as $row
        ) {
            fputcsv(
                $stream,
                array_map(
                    static fn (
                        string $column
                    ): string => $row[$column]
                        ?? '',
                    array_keys(
                        $columns
                    )
                )
            );
        }

        rewind(
            $stream
        );

        $contents =
            stream_get_contents(
                $stream
            );

        fclose(
            $stream
        );

        if ($contents === false) {
            throw new \RuntimeException(
                'Unable to read generated Activity Log CSV.'
            );
        }

        /*
         * UTF-8 BOM keeps accented French text and other Unicode content
         * reliable when the export is opened directly in Microsoft Excel.
         */
        return "\xEF\xBB\xBF"
            .$contents;
    }

    /**
     * V1.0.7: render the same shared projection as an XLSX workbook so
     * Activity Log downloads match the PDF/XLSX/CSV rule used everywhere
     * else in Patrimoine.
     *
     * @param  Collection<int, ActivityLog>  $events
     */
    public function xlsx(
        Collection $events
    ): string {
        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-activity-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary Activity Log XLSX file.'
            );
        }

        $writer =
            new Writer();

        try {
            $writer->openToFile(
                $path
            );

            $columns =
                $this->columns();

            $writer->addRow(
                Row::fromValues(
                    array_values(
                        $columns
                    )
                )
            );

            foreach (
                $this->rows(
                    $events
                ) as $row
            ) {
                $writer->addRow(
                    Row::fromValues(
                        array_map(
                            static fn (
                                string $column
                            ): string =>
                                $row[$column]
                                ?? '',
                            array_keys(
                                $columns
                            )
                        )
                    )
                );
            }

            $writer->close();

            $contents =
                file_get_contents(
                    $path
                );

            if ($contents === false) {
                throw new RuntimeException(
                    'Unable to read generated Activity Log XLSX file.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink(
                    $path
                );
            }
        }
    }

    /**
     * Translate a frozen role value.
     */
    public function role(
        ?string $role
    ): string {
        if ($role === null || $role === '') {
            return '';
        }

        $roles =
            $this->translationArray(
                'activity_log.roles'
            );

        $translated =
            $roles[$role]
            ?? null;

        return $this->text(
            $translated,
            $this->humanize(
                $role
            )
        );
    }

    /**
     * Translate one literal Activity Log action identifier.
     *
     * Action identifiers contain periods, so Laravel dotted translation
     * access cannot safely address them individually. Retrieve the complete
     * actions catalogue and perform a literal PHP array lookup instead.
     */
    public function action(
        ?string $action
    ): string {
        if ($action === null || $action === '') {
            return '';
        }

        $actions =
            $this->translationArray(
                'activity_log.actions'
            );

        $translated =
            $actions[$action]
            ?? null;

        return $this->text(
            $translated,
            $this->humanize(
                $action
            )
        );
    }

    /**
     * Translate a stable entity/record type.
     */
    public function entityType(
        ?string $entityType
    ): string {
        if (
            $entityType === null
            || $entityType === ''
        ) {
            return '';
        }

        $entities =
            $this->translationArray(
                'activity_log.entities'
            );

        $translated =
            $entities[$entityType]
            ?? null;

        return $this->text(
            $translated,
            $this->humanize(
                $entityType
            )
        );
    }

    /**
     * Format an Activity Log instant with both date and time.
     *
     * ApplicationPresentationFormatter::date() intentionally handles
     * date-only business values. Activity Log events are instants and must
     * preserve their time component, so they use a dedicated formatter
     * based on the same organisation language configuration.
     */
    public function timestamp(
        DateTimeInterface|string|null $value
    ): string {
        if ($value === null || $value === '') {
            return '';
        }

        $language =
            strtolower(
                $this->localeService->language()
            );

        $locale =
            (string) config(
                "patrimoine.languages.{$language}.date_locale",
                'en_GB'
            );

        $date =
            $this->timestampValue(
                $value
            );

        if ($date === null) {
            return (string) $value;
        }

        $formatter =
            new IntlDateFormatter(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                config('app.timezone'),
                IntlDateFormatter::GREGORIAN,
                'dd MMM yyyy, HH:mm:ss'
            );

        $formatted =
            $formatter->format(
                $date
            );

        return $formatted === false
            ? $date->format(
                'd M Y, H:i:s'
            )
            : $formatted;
    }

    /**
     * Serialize historical structured context without losing Unicode.
     *
     * Empty/null structures remain visually empty. Boolean and zero values
     * inside structures are preserved by JSON serialization.
     */
    public function structured(
        mixed $value
    ): string {
        if (
            $value === null
            || $value === []
        ) {
            return '';
        }

        if (! is_array($value)) {
            return $this->text(
                $value
            );
        }

        $encoded =
            json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRETTY_PRINT
            );

        return $encoded === false
            ? ''
            : $encoded;
    }

    /**
     * Return the active language's complete translation array.
     *
     * This is required for dictionaries whose literal keys may themselves
     * contain periods, such as "payment.recorded".
     *
     * @return array<string, mixed>
     */
    private function translationArray(
        string $key
    ): array {
        $translated =
            __($key);

        return is_array($translated)
            ? $translated
            : [];
    }

    /**
     * Return one ordinary scalar translation with a stable fallback.
     */
    private function translation(
        string $key,
        string $fallback
    ): string {
        $translated =
            __($key);

        if (
            ! is_string($translated)
            || $translated === $key
        ) {
            return $fallback;
        }

        return $translated;
    }

    /**
     * Normalize nullable scalar presentation.
     */
    private function text(
        mixed $value,
        string $fallback = ''
    ): string {
        if (
            $value === null
            || $value === ''
        ) {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value
                ? $this->translation(
                    'activity_log.yes',
                    'Yes'
                )
                : $this->translation(
                    'activity_log.no',
                    'No'
                );
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $fallback;
    }

    /**
     * Convert an unknown stable identifier into a readable fallback label.
     */
    private function humanize(
        string $value
    ): string {
        return ucwords(
            str_replace(
                [
                    '.',
                    '_',
                    '-',
                ],
                ' ',
                $value
            )
        );
    }

    /**
     * Normalize a stored Activity Log timestamp for Intl presentation.
     */
    private function timestampValue(
        DateTimeInterface|string $value
    ): ?DateTimeInterface {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        try {
            return CarbonImmutable::parse(
                $value,
                config('app.timezone')
            );
        } catch (\Throwable) {
            return null;
        }
    }
}

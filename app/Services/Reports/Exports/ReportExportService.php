<?php

namespace App\Services\Reports\Exports;

use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Converts an already-calculated Patrimoine report into downloadable
 * presentation formats.
 *
 * This service deliberately performs no accounting calculations.
 * Report values must come from the dedicated report services so JSON,
 * PDF and CSV outputs always use the same financial interpretation.
 */
class ReportExportService
{
    public function __construct(

        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter

    ) {}

    /**
     * Render a report as PDF and return the raw PDF contents.
     */
    public function pdf(

        string $title,

        array $report

    ): string {

        return Pdf::loadView(

            'reports.report',

            [

                'title' => $title,

                'sections' => $this->sections($report),

                'managingOrganisation' => $this->identity->managingOrganisation(),

                'generatedAt' => now(),

                'formatter' => $this->formatter,

            ]

        )

            ->setPaper('a4')

            ->output();

    }

    /**
     * Render a report as CSV.
     */
    public function csv(array $report): string
    {
        $stream = fopen(
            'php://temp',
            'r+'
        );

        if ($stream === false) {
            throw new \RuntimeException(
                'Unable to create report CSV stream.'
            );
        }

        foreach ($this->sections($report) as $section) {
            fputcsv(
                $stream,
                [
                    $section['title'],
                ]
            );

            if ($section['type'] === 'pairs') {
                fputcsv(
                    $stream,
                    [
                        __('reports.field'),
                        __('reports.value'),
                    ]
                );

                foreach ($section['rows'] as $row) {
                    fputcsv(
                        $stream,
                        [
                            $row['label'],
                            $row['value'],
                        ]
                    );
                }
            }

            if ($section['type'] === 'table') {
                if ($section['headers'] !== []) {
                    fputcsv(
                        $stream,
                        $section['headers']
                    );
                }

                foreach ($section['rows'] as $row) {
                    fputcsv(
                        $stream,
                        $row
                    );
                }
            }

            /*
             * Blank separator between report sections.
             */
            fputcsv(
                $stream,
                []
            );
        }

        rewind($stream);

        $contents = stream_get_contents($stream);

        fclose($stream);

        if ($contents === false) {
            throw new \RuntimeException(
                'Unable to read generated report CSV.'
            );
        }

        /*
         * UTF-8 BOM improves compatibility when CSV files are opened
         * directly in Microsoft Excel.
         */
        return "\xEF\xBB\xBF".$contents;
    }

    /**
     * Normalize different report structures into presentation sections.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sections(array $report): array
    {
        $sections = [];

        foreach ($report as $key => $value) {
            if (! is_array($value)) {
                $sections[] = [
                    'title' => $this->humanize($key),
                    'type' => 'pairs',
                    'headers' => [],
                    'rows' => [
                        [
                            'label' => $this->humanize($key),
                            'value' => $this->displayValue(
                                $value,
                                (string) $key
                            ),
                        ],
                    ],
                ];

                continue;
            }

            if (array_is_list($value)) {
                $sections[] = $this->tableSection(
                    $key,
                    $value
                );

                continue;
            }

            $sections[] = [
                'title' => $this->humanize($key),
                'type' => 'pairs',
                'headers' => [],
                'rows' => $this->pairRows($value),
            ];
        }

        return $sections;
    }

    /**
     * Convert an associative array to label/value rows.
     *
     * Nested associative structures are represented with dotted labels.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function pairRows(
        array $values,
        string $prefix = ''
    ): array {
        $rows = [];

        foreach ($values as $key => $value) {
            $label = $prefix === ''
                ? $this->humanize((string) $key)
                : $prefix.' / '.$this->humanize((string) $key);

            if (
                is_array($value)
                && ! array_is_list($value)
            ) {
                $rows = array_merge(
                    $rows,
                    $this->pairRows(
                        $value,
                        $label
                    )
                );

                continue;
            }

            if (
                is_array($value)
                && array_is_list($value)
            ) {
                $rows[] = [
                    'label' => $label,
                    'value' => (string) count($value),
                ];

                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $this->displayValue(
                    $value,
                    (string) $key
                ),
            ];
        }

        return $rows;
    }

    /**
     * Normalize a list of report rows into a table section.
     *
     * @return array<string, mixed>
     */
    private function tableSection(
        string $key,
        array $items
    ): array {
        if ($items === []) {
            return [
                'title' => $this->humanize($key),
                'type' => 'table',
                'headers' => [],
                'rows' => [],
            ];
        }

        $first = $items[0];

        if (! is_array($first)) {
            return [
                'title' => $this->humanize($key),
                'type' => 'table',
                'headers' => [
                    __('reports.value'),
                ],
                'rows' => array_map(
                    fn ($value): array => [
                        $this->displayValue(
                            $value,
                            null
                        ),
                    ],
                    $items
                ),
            ];
        }

        $keys = array_keys($first);

        $headers = array_map(
            fn ($column): string => $this->humanize((string) $column),
            $keys
        );

        $rows = [];

        foreach ($items as $item) {
            $row = [];

            foreach ($keys as $column) {
                $row[] = $this->displayValue(
                    $item[$column] ?? null,
                    (string) $column
                );
            }

            $rows[] = $row;
        }

        return [
            'title' => $this->humanize($key),
            'type' => 'table',
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Convert stored/report values into readable export presentation.
     *
     * Report services deliberately keep raw business values. Formatting
     * happens only here so JSON/API consumers continue receiving integers,
     * ISO dates and the existing report structure.
     *
     * Semantic field names are used instead of guessing from numeric values:
     * an ID or count may also be an integer but must never become money.
     */
    private function displayValue(
        mixed $value,
        ?string $field
    ): string {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value
                ? __('reports.yes')
                : __('reports.no');
        }

        if (is_array($value)) {
            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            ) ?: '';
        }

        if (
            $field !== null
            && $this->isDateField($field)
        ) {
            return $this->formatter->date(
                is_string($value)
                    ? $value
                    : (string) $value
            );
        }

        if (
            $field !== null
            && $this->isMoneyField($field)
        ) {
            return $this->formatter->money(
                $value
            );
        }

        return (string) $value;
    }

    /**
     * Report date fields use stable semantic names.
     */
    private function isDateField(
        string $field
    ): bool {
        if (
            in_array(
                $field,
                [
                    'date',
                    'from',
                    'to',
                    'as_of',
                ],
                true
            )
        ) {
            return true;
        }

        return str_ends_with(
            $field,
            '_date'
        );
    }

    /**
     * Report monetary fields.
     *
     * Keep this explicit enough that IDs, counts and percentages cannot be
     * accidentally formatted as money while still covering the shared
     * financial vocabulary used by Patrimoine report services.
     */
    private function isMoneyField(
        string $field
    ): bool {
        if (
            in_array(
                $field,
                [
                    'amount',
                    'paid',
                    'settled',
                    'outstanding',
                    'allocated',
                    'unallocated',

                    'credits',
                    'debits',

                    'invoiced',
                    'invoice_settled',
                    'cash_received',

                    'opening_balance',
                    'closing_balance',

                    'rent_entitlement',
                    'owner_rent_entitlement',

                    'owner_deposits',

                    'management_fees',
                    'management_fee_vat',
                    'agent_commissions',

                    'expenses',
                    'property_expenses',
                    'owner_expenses',

                    'payouts',
                    'owner_payouts',

                    'adjustments_credit',
                    'adjustments_debit',

                    'reserve_transfers_credit',
                    'reserve_transfers_debit',

                    /*
                     * Owner statement tenant rows.
                     */
                    'monthly_rent',
                    'rent_collected',

                    'rent_invoiced',
                    'security_deposit_debt_invoiced',

                    'rent_outstanding',
                    'security_deposit_debt_outstanding',
                    'total_outstanding',

                    'rent_reserve',
                    'consumable_advance',
                    'security_deposit',

                    'rent_reserve_balance',
                    'consumable_advance_balance',
                    'security_deposit_balance',

                    'owner_funds_held',

                    /*
                     * Snapshot-report vocabulary (arrears aging buckets and
                     * held funds). These reports currently ship dedicated
                     * exporters, but the shared vocabulary is registered
                     * here so any report routed through the generic
                     * exporter formats them as money.
                     */
                    'total',
                    'balance',
                    'total_held',
                    'current',
                    'days_31_60',
                    'days_61_90',
                    'over_90',
                ],
                true
            )
        ) {
            return true;
        }

        return str_ends_with(
            $field,
            '_amount'
        );
    }

    /**
     * Convert stable report keys to localised human-facing labels.
     *
     * Report/API keys remain unchanged. Only export presentation is
     * translated here so PDF and CSV localisation cannot alter report
     * calculations or the JSON contract.
     */
    private function humanize(string $value): string
    {
        $translationKey = 'reports.labels.'.$value;

        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return ucwords(
            str_replace(
                [
                    '_',
                    '-',
                ],
                ' ',
                $value
            )
        );
    }

    /**
     * Render the same flattened report representation used by CSV
     * as a native XLSX workbook.
     */
    public function xlsx(array $report): string
    {
        $csv = $this->csv($report);

        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new \RuntimeException(
                'Unable to create temporary XLSX stream.'
            );
        }

        /*
         * OpenSpout writes to a filesystem path, so create a temporary file
         * and return its bytes after the workbook has been closed.
         */
        $path = tempnam(
            sys_get_temp_dir(),
            'patrimoine-report-'
        );

        if ($path === false) {
            fclose($stream);

            throw new \RuntimeException(
                'Unable to create temporary XLSX file.'
            );
        }

        try {
            $writer = new Writer;
            $writer->openToFile($path);

            $csvStream = fopen('php://temp', 'w+b');

            if ($csvStream === false) {
                throw new \RuntimeException(
                    'Unable to create temporary CSV stream.'
                );
            }

            fwrite($csvStream, $csv);
            rewind($csvStream);

            while (
                ($values = fgetcsv(
                    $csvStream,
                    null,
                    ',',
                    '"',
                    ''
                )) !== false
            ) {
                /*
                 * Keep the generic export faithful to CSV presentation.
                 * Empty separator rows are retained.
                 */
                $writer->addRow(
                    Row::fromValues(
                        array_map(
                            static fn ($value) => $value === null ? '' : $value,
                            $values
                        )
                    )
                );
            }

            fclose($csvStream);
            $writer->close();

            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new \RuntimeException(
                    'Unable to read generated XLSX file.'
                );
            }

            return $contents;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

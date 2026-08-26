<?php

namespace App\Services\Reports;

use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class ArrearsReportExportService
{
    public function __construct(
        private readonly ArrearsReportService $reports,
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function projection(
        array $filters = []
    ): array {
        return $this->reports->generate(
            $filters
        );
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            'tenant' => __('reports.labels.tenant'),

            'lease' => __('reports.labels.lease'),

            'building' => __('reports.labels.building'),

            'unit' => __('reports.labels.unit'),

            'invoice_count' => __('reports.labels.invoices_count'),

            'current' => __('reports.labels.current_0_30'),

            'days_31_60' => __('reports.labels.days_31_60'),

            'days_61_90' => __('reports.labels.days_61_90'),

            'over_90' => __('reports.labels.over_90'),

            'total' => __('reports.labels.total'),
        ];
    }

    /**
     * The already-calculated projection may be supplied to avoid
     * regenerating the report for one export.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>|null  $projection
     * @return list<array<string, string>>
     */
    public function rows(
        array $filters = [],
        ?array $projection = null
    ): array {
        $projection ??=
            $this->projection(
                $filters
            );

        return collect(
            $projection['tenants']
            ?? []
        )
            ->map(
                fn (array $tenant): array => [
                    'tenant' => (string) (
                        $tenant['tenant']['name']
                        ?? ''
                    ),

                    'lease' => isset($tenant['lease']['id'])
                        ? '#'.$tenant['lease']['id']
                        : '',

                    'building' => (string) (
                        $tenant['building']['name']
                        ?? ''
                    ),

                    'unit' => (string) (
                        $tenant['unit']['name']
                        ?? ''
                    ),

                    'invoice_count' => (string) (
                        $tenant['invoice_count']
                        ?? 0
                    ),

                    'current' => $this->formatter->money(
                        $tenant['current']
                        ?? 0
                    ),

                    'days_31_60' => $this->formatter->money(
                        $tenant['days_31_60']
                        ?? 0
                    ),

                    'days_61_90' => $this->formatter->money(
                        $tenant['days_61_90']
                        ?? 0
                    ),

                    'over_90' => $this->formatter->money(
                        $tenant['over_90']
                        ?? 0
                    ),

                    'total' => $this->formatter->money(
                        $tenant['total']
                        ?? 0
                    ),
                ]
            )
            ->values()
            ->all();
    }

    /**
     * Leading aging-total rows shared by the CSV and XLSX writers so the
     * flat exports carry the same summary as the screen and the PDF.
     *
     * @param  array<string, mixed>  $projection
     * @return list<list<string>>
     */
    private function summaryRows(
        array $projection
    ): array {
        $totals =
            $projection['totals']
            ?? [];

        return [
            [
                __('reports.as_of'),
                $this->formatter->date(
                    $projection['as_of']
                    ?? null
                ),
            ],
            [
                __('reports.labels.invoices_count'),
                (string) (
                    $totals['invoice_count']
                    ?? 0
                ),
            ],
            [
                __('reports.labels.current_0_30'),
                $this->formatter->money(
                    $totals['current']
                    ?? 0
                ),
            ],
            [
                __('reports.labels.days_31_60'),
                $this->formatter->money(
                    $totals['days_31_60']
                    ?? 0
                ),
            ],
            [
                __('reports.labels.days_61_90'),
                $this->formatter->money(
                    $totals['days_61_90']
                    ?? 0
                ),
            ],
            [
                __('reports.labels.over_90'),
                $this->formatter->money(
                    $totals['over_90']
                    ?? 0
                ),
            ],
            [
                __('reports.labels.grand_total'),
                $this->formatter->money(
                    $totals['total']
                    ?? 0
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function pdf(
        array $filters = []
    ): string {
        $projection =
            $this->projection(
                $filters
            );

        return Pdf::loadView(
            'reports.arrears-export',
            [
                'columns' => $this->columns(),

                'rows' => $this->rows(
                    $filters,
                    $projection
                ),

                'totals' => $projection['totals']
                    ?? [],

                'asOf' => $projection['as_of']
                    ?? null,

                'managingOrganisation' => $this->identity->managingOrganisation(),

                'generatedAt' => now(),

                'formatter' => $this->formatter,
            ]
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->output();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function csv(
        array $filters = []
    ): string {
        $projection =
            $this->projection(
                $filters
            );

        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create Arrears Report CSV stream.'
            );
        }

        foreach (
            $this->summaryRows(
                $projection
            ) as $summaryRow
        ) {
            fputcsv(
                $stream,
                $summaryRow,
                ',',
                '"',
                ''
            );
        }

        fputcsv(
            $stream,
            [''],
            ',',
            '"',
            ''
        );

        fputcsv(
            $stream,
            array_values(
                $this->columns()
            ),
            ',',
            '"',
            ''
        );

        foreach (
            $this->rows(
                $filters,
                $projection
            ) as $row
        ) {
            fputcsv(
                $stream,
                array_values(
                    $row
                ),
                ',',
                '"',
                ''
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
            throw new RuntimeException(
                'Unable to read Arrears Report CSV.'
            );
        }

        return "\xEF\xBB\xBF"
            .$contents;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function xlsx(
        array $filters = []
    ): string {
        $projection =
            $this->projection(
                $filters
            );

        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-arrears-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary Arrears Report XLSX file.'
            );
        }

        $writer =
            new Writer;

        try {
            $writer->openToFile(
                $path
            );

            foreach (
                $this->summaryRows(
                    $projection
                ) as $summaryRow
            ) {
                $writer->addRow(
                    Row::fromValues(
                        $summaryRow
                    )
                );
            }

            $writer->addRow(
                Row::fromValues(
                    ['']
                )
            );

            $writer->addRow(
                Row::fromValues(
                    array_values(
                        $this->columns()
                    )
                )
            );

            foreach (
                $this->rows(
                    $filters,
                    $projection
                ) as $row
            ) {
                $writer->addRow(
                    Row::fromValues(
                        array_values(
                            $row
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
                    'Unable to read Arrears Report XLSX.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

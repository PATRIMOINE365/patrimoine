<?php

namespace App\Services\Reports;

use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class ArrearsReportExportService
{
    public function __construct(
        private readonly ArrearsReportService $reports,
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

            'property' => __('reports.labels.property'),

            'invoice_count' => __('reports.labels.invoices_count'),

            'current' => __('reports.labels.current_0_30'),

            'days_31_60' => __('reports.labels.days_31_60'),

            'days_61_90' => __('reports.labels.days_61_90'),

            'over_90' => __('reports.labels.over_90'),

            'total' => __('reports.labels.total'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, string>>
     */
    public function rows(
        array $filters = []
    ): array {
        $projection =
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

                    'property' => collect([
                        $tenant['building']['name']
                            ?? null,

                        $tenant['unit']['name']
                            ?? null,
                    ])
                        ->filter()
                        ->implode(' · '),

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
                    $filters
                ),

                'totals' => $projection['totals']
                    ?? [],

                'asOf' => $projection['as_of']
                    ?? null,

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
                $filters
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

            $writer->addRow(
                Row::fromValues(
                    array_values(
                        $this->columns()
                    )
                )
            );

            foreach (
                $this->rows(
                    $filters
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

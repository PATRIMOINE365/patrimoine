<?php

namespace App\Services\Reports;

use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class OccupancyReportExportService
{
    public function __construct(
        private readonly OccupancyReportService $reports,
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
            'building' => __('reports.labels.building'),

            'units' => __('reports.labels.units'),

            'occupied' => __('reports.labels.occupied'),

            'vacant' => __('reports.labels.vacant'),

            'occupancy_rate' => __('reports.labels.occupancy_rate'),

            'commercial_units' => __('reports.labels.commercial_units'),
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
            $projection['buildings']
            ?? []
        )
            ->map(
                fn (array $building): array => [
                    'building' => (string) (
                        $building['name']
                        ?? ''
                    ),

                    'units' => (string) (
                        $building['units']
                        ?? 0
                    ),

                    'occupied' => (string) (
                        $building['occupied']
                        ?? 0
                    ),

                    'vacant' => (string) (
                        $building['vacant']
                        ?? 0
                    ),

                    'occupancy_rate' => (
                        $building['occupancy_rate']
                        ?? 0
                    ).'%',

                    'commercial_units' => (string) (
                        $building['commercial_units']
                        ?? 0
                    ),
                ]
            )
            ->values()
            ->all();
    }

    /**
     * Leading summary rows shared by the CSV and XLSX writers: snapshot
     * totals plus the commercial/residential classification split.
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

        $classification =
            $projection['classification']
            ?? [];

        $rows = [
            [
                __('reports.as_of'),
                $this->formatter->date(
                    $projection['as_of']
                    ?? null
                ),
            ],
            [
                __('reports.labels.units'),
                (string) ($totals['units'] ?? 0),
            ],
            [
                __('reports.labels.occupied'),
                (string) ($totals['occupied'] ?? 0),
            ],
            [
                __('reports.labels.vacant'),
                (string) ($totals['vacant'] ?? 0),
            ],
            [
                __('reports.labels.occupancy_rate'),
                ($totals['occupancy_rate'] ?? 0).'%',
            ],
            [''],
            [
                __('reports.labels.classification'),
                __('reports.labels.units'),
                __('reports.labels.occupied'),
                __('reports.labels.vacant'),
                __('reports.labels.occupancy_rate'),
            ],
        ];

        foreach (['commercial', 'residential'] as $segment) {
            $rows[] = [
                __('reports.labels.'.$segment),
                (string) ($classification[$segment]['units'] ?? 0),
                (string) ($classification[$segment]['occupied'] ?? 0),
                (string) ($classification[$segment]['vacant'] ?? 0),
                ($classification[$segment]['occupancy_rate'] ?? 0).'%',
            ];
        }

        return $rows;
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
            'reports.occupancy-export',
            [
                'columns' => $this->columns(),

                'rows' => $this->rows(
                    $filters,
                    $projection
                ),

                'totals' => $projection['totals']
                    ?? [],

                'classification' => $projection['classification']
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
                'Unable to create Occupancy Report CSV stream.'
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
                'Unable to read Occupancy Report CSV.'
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
                'patrimoine-occupancy-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary Occupancy Report XLSX file.'
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
                    'Unable to read Occupancy Report XLSX.'
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

<?php

namespace App\Services\Reports;

use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class OccupancyReportExportService
{
    public function __construct(
        private readonly OccupancyReportService $reports,
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
                    $filters
                ),

                'totals' => $projection['totals']
                    ?? [],

                'classification' => $projection['classification']
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
                'Unable to create Occupancy Report CSV stream.'
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

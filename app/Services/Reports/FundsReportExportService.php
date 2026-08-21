<?php

namespace App\Services\Reports;

use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class FundsReportExportService
{
    public function __construct(
        private readonly FundsReportService $reports,
        private readonly ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function projection(): array
    {
        return $this->reports->generate();
    }

    /**
     * @return array<string, string>
     */
    public function tenantColumns(): array
    {
        return [
            'tenant' => __('reports.labels.tenant'),

            'property' => __('reports.labels.property'),

            'rent_reserve' => __('reports.labels.rent_reserve'),

            'consumable_advance' => __('reports.labels.consumable_advance'),

            'security_deposit' => __('reports.labels.security_deposit'),

            'total' => __('reports.labels.total'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function ownerColumns(): array
    {
        return [
            'owner' => __('reports.labels.owner'),

            'balance' => __('reports.labels.balance'),
        ];
    }

    /**
     * @param  array<string, mixed>  $projection
     * @return list<array<string, string>>
     */
    public function tenantRows(
        array $projection
    ): array {
        return collect(
            $projection['tenant_funds']['tenants']
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

                    'rent_reserve' => $this->formatter->money(
                        $tenant['rent_reserve']
                        ?? 0
                    ),

                    'consumable_advance' => $this->formatter->money(
                        $tenant['consumable_advance']
                        ?? 0
                    ),

                    'security_deposit' => $this->formatter->money(
                        $tenant['security_deposit']
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
     * @param  array<string, mixed>  $projection
     * @return list<array<string, string>>
     */
    public function ownerRows(
        array $projection
    ): array {
        return collect(
            $projection['owner_funds']['owners']
            ?? []
        )
            ->map(
                fn (array $owner): array => [
                    'owner' => (string) (
                        $owner['owner']['name']
                        ?? ''
                    ),

                    'balance' => $this->formatter->money(
                        $owner['balance']
                        ?? 0
                    ),
                ]
            )
            ->values()
            ->all();
    }

    public function pdf(): string
    {
        $projection =
            $this->projection();

        return Pdf::loadView(
            'reports.funds-export',
            [
                'tenantColumns' => $this->tenantColumns(),

                'tenantRows' => $this->tenantRows(
                    $projection
                ),

                'ownerColumns' => $this->ownerColumns(),

                'ownerRows' => $this->ownerRows(
                    $projection
                ),

                'tenantSummary' => $projection['tenant_funds']['summary']
                    ?? [],

                'ownerSummary' => $projection['owner_funds']['summary']
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

    public function csv(): string
    {
        $stream =
            fopen(
                'php://temp',
                'r+'
            );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create Funds Report CSV stream.'
            );
        }

        foreach (
            $this->sections() as $section
        ) {
            fputcsv(
                $stream,
                [$section['title']],
                ',',
                '"',
                ''
            );

            fputcsv(
                $stream,
                array_values(
                    $section['columns']
                ),
                ',',
                '"',
                ''
            );

            foreach ($section['rows'] as $row) {
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

            if (! $section['last']) {
                fputcsv(
                    $stream,
                    [''],
                    ',',
                    '"',
                    ''
                );
            }
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
                'Unable to read Funds Report CSV.'
            );
        }

        return "\xEF\xBB\xBF"
            .$contents;
    }

    public function xlsx(): string
    {
        $path =
            tempnam(
                sys_get_temp_dir(),
                'patrimoine-funds-'
            );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to create temporary Funds Report XLSX file.'
            );
        }

        $writer =
            new Writer;

        try {
            $writer->openToFile(
                $path
            );

            foreach (
                $this->sections() as $section
            ) {
                $writer->addRow(
                    Row::fromValues(
                        [$section['title']]
                    )
                );

                $writer->addRow(
                    Row::fromValues(
                        array_values(
                            $section['columns']
                        )
                    )
                );

                foreach ($section['rows'] as $row) {
                    $writer->addRow(
                        Row::fromValues(
                            array_values(
                                $row
                            )
                        )
                    );
                }

                if (! $section['last']) {
                    $writer->addRow(
                        Row::fromValues(
                            ['']
                        )
                    );
                }
            }

            $writer->close();

            $contents =
                file_get_contents(
                    $path
                );

            if ($contents === false) {
                throw new RuntimeException(
                    'Unable to read Funds Report XLSX.'
                );
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Flat tabular sections shared by the CSV and XLSX writers.
     *
     * @return list<array{
     *     title: string,
     *     columns: array<string, string>,
     *     rows: list<array<string, string>>,
     *     last: bool
     * }>
     */
    private function sections(): array
    {
        $projection =
            $this->projection();

        return [
            [
                'title' => __('reports.labels.tenant_funds'),

                'columns' => $this->tenantColumns(),

                'rows' => $this->tenantRows(
                    $projection
                ),

                'last' => false,
            ],

            [
                'title' => __('reports.labels.owner_funds'),

                'columns' => $this->ownerColumns(),

                'rows' => $this->ownerRows(
                    $projection
                ),

                'last' => true,
            ],
        ];
    }
}

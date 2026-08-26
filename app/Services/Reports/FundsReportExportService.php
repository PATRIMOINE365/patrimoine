<?php

namespace App\Services\Reports;

use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class FundsReportExportService
{
    public function __construct(
        private readonly FundsReportService $reports,
        private readonly ApplicationIdentityService $identity,
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

            'lease' => __('reports.labels.lease'),

            'building' => __('reports.labels.building'),

            'unit' => __('reports.labels.unit'),

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
            if ($section['title'] !== null) {
                fputcsv(
                    $stream,
                    [$section['title']],
                    ',',
                    '"',
                    ''
                );
            }

            if ($section['columns'] !== []) {
                fputcsv(
                    $stream,
                    array_values(
                        $section['columns']
                    ),
                    ',',
                    '"',
                    ''
                );
            }

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
                if ($section['title'] !== null) {
                    $writer->addRow(
                        Row::fromValues(
                            [$section['title']]
                        )
                    );
                }

                if ($section['columns'] !== []) {
                    $writer->addRow(
                        Row::fromValues(
                            array_values(
                                $section['columns']
                            )
                        )
                    );
                }

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
     * Each fund group leads with its summary rows (per-type balances and
     * account counts) before the detail table, mirroring the screen and
     * PDF presentation.
     *
     * @return list<array{
     *     title: ?string,
     *     columns: array<string, string>,
     *     rows: list<array<int|string, string>>,
     *     last: bool
     * }>
     */
    private function sections(): array
    {
        $projection =
            $this->projection();

        $tenantSummary =
            $projection['tenant_funds']['summary']
            ?? [];

        $ownerSummary =
            $projection['owner_funds']['summary']
            ?? [];

        $tenantSummaryRows = [];

        foreach (
            [
                'rent_reserve',
                'consumable_advance',
                'security_deposit',
            ] as $fundType
        ) {
            $tenantSummaryRows[] = [
                __('reports.labels.'.$fundType),

                (string) (
                    $tenantSummary[$fundType]['account_count']
                    ?? 0
                ),

                $this->formatter->money(
                    $tenantSummary[$fundType]['total_held']
                    ?? 0
                ),
            ];
        }

        $tenantSummaryRows[] = [
            __('reports.labels.total'),

            '',

            $this->formatter->money(
                $tenantSummary['total_held']
                ?? 0
            ),
        ];

        return [
            [
                'title' => null,

                'columns' => [],

                'rows' => [
                    [
                        __('reports.as_of'),
                        $this->formatter->date(
                            $projection['as_of']
                            ?? null
                        ),
                    ],
                ],

                'last' => false,
            ],

            [
                'title' => __('reports.labels.tenant_funds'),

                'columns' => [
                    'type' => __('reports.labels.type'),

                    'account_count' => __('reports.labels.account_count'),

                    'total_held' => __('reports.labels.total_held'),
                ],

                'rows' => $tenantSummaryRows,

                'last' => false,
            ],

            [
                'title' => null,

                'columns' => $this->tenantColumns(),

                'rows' => $this->tenantRows(
                    $projection
                ),

                'last' => false,
            ],

            [
                'title' => __('reports.labels.owner_funds'),

                'columns' => [
                    'account_count' => __('reports.labels.account_count'),

                    'total_held' => __('reports.labels.total_held'),
                ],

                'rows' => [
                    [
                        (string) (
                            $ownerSummary['account_count']
                            ?? 0
                        ),

                        $this->formatter->money(
                            $ownerSummary['total_held']
                            ?? 0
                        ),
                    ],
                ],

                'last' => false,
            ],

            [
                'title' => null,

                'columns' => $this->ownerColumns(),

                'rows' => $this->ownerRows(
                    $projection
                ),

                'last' => true,
            ],
        ];
    }
}
